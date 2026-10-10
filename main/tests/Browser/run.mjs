import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdtemp, writeFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { resolve, join } from 'node:path';
import { createServer } from 'node:net';
import { once } from 'node:events';

const root = resolve(import.meta.dirname, '../..');
const php = process.env.KODY_TEST_PHP || 'php';
const database = `kody_browser_${randomBytes(16).toString('hex')}`;
const temporary = await mkdtemp(join(tmpdir(), 'kody-browser-'));
const listener = createServer();
await new Promise((ready, reject) => listener.once('error', reject).listen(0, '127.0.0.1', ready));
const port = listener.address().port;
await new Promise(resolve => listener.close(resolve));
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', KODY_BROWSER_ISOLATED: '1',
    APP_KEY: `base64:${randomBytes(32).toString('base64')}`, APP_URL: `http://127.0.0.1:${port}`,
    DB_CONNECTION: 'pgsql', DB_HOST: '127.0.0.1', DB_DATABASE: database, DB_SSLMODE: 'disable',
    SESSION_DRIVER: 'database', SESSION_SECURE_COOKIE: 'false', SESSION_COOKIE: `browser_${port}`,
    CACHE_STORE: 'array', QUEUE_CONNECTION: 'database', MAIL_MAILER: 'array',
    FILESYSTEM_DISK: 'local', INSTRUCTOR_CREDENTIAL_DISK: 'local',
    ACCOUNT_VERIFICATION_MAILER: 'array', ACCOUNT_NOTIFICATION_MAILER: 'array', ACCOUNT_RECOVERY_MAILER: 'array',
    GOOGLE_AUTH_ENABLED: 'false', JUDGE0_ENABLED: 'false', XENDIT_ENABLED: 'false',
    XENDIT_CONTRACT_VERIFIED: 'false', BCRYPT_ROUNDS: '4', LOG_CHANNEL: 'stderr',
    KODY_BROWSER_BASE_URL: `http://127.0.0.1:${port}`, KODY_BROWSER_MANIFEST: join(temporary, 'manifest.json'), KODY_TEST_PHP: php,
    // Database URLs can override the explicit isolation configuration in Laravel.
    DB_URL: '', DATABASE_URL: '', GOOGLE_CLIENT_SECRET: '', GOOGLE_CLIENT_ID: '', JUDGE0_RAPIDAPI_KEY: '',
    JUDGE0_AUTH_TOKEN: '', XENDIT_SECRET_KEY: '', XENDIT_CALLBACK_TOKEN: '', SENDGRID_API_KEY: '',
    MAIL_USERNAME: '', MAIL_PASSWORD: '', AWS_ACCESS_KEY_ID: '', AWS_SECRET_ACCESS_KEY: '', AWS_SESSION_TOKEN: '' };
function fixture(operation, environment = env) {
    const result = spawnSync(php, ['tests/Browser/support/fixture.php', operation], { cwd: root, env: environment, encoding: 'utf8', windowsHide: true, timeout: 120_000 });
    if (result.status !== 0) throw new Error(`Isolated browser ${operation} failed. ${result.stderr || ''}`);
    return result.stdout;
}
let created = false;
let server;
let runner;
let interrupted = false;
let status = 1;
const interrupt = () => { interrupted = true; runner?.kill(); server?.kill(); };
process.on('SIGINT', interrupt);
process.on('SIGTERM', interrupt);
let restoredCreated = false;
const restoredEnv = { ...env, DB_DATABASE: `kody_browser_${randomBytes(16).toString('hex')}` };
function postgres(program, arguments_) {
    const executable = process.env.KODY_TEST_PG_BIN ? join(process.env.KODY_TEST_PG_BIN, `${program}${process.platform === 'win32' ? '.exe' : ''}`) : program;
    const result = spawnSync(executable, arguments_, { cwd: root, windowsHide: true, encoding: 'utf8', timeout: 120_000,
        env: { ...env, PGHOST: env.DB_HOST, PGPORT: env.DB_PORT || '5432', PGUSER: env.DB_USERNAME, PGPASSWORD: env.DB_PASSWORD } });
    if (result.status !== 0) throw new Error(`Isolated ${program} failed; no credentials or raw database output emitted.`);
}
try {
    fixture('create'); created = true;
    const rehearsal = process.argv.includes('--rehearse');
    fixture(rehearsal ? 'migrate-baseline' : 'migrate');
    await writeFile(env.KODY_BROWSER_MANIFEST, fixture('seed'), { mode: 0o600 });
    if (interrupted) throw new Error('Isolated verification interrupted.');
    if (rehearsal) {
        fixture('seed-history');
        const beforeUpgrade = JSON.parse(fixture('fingerprint-legacy'));
        fixture('migrate');
        const afterUpgrade = JSON.parse(fixture('fingerprint-legacy'));
        for (const table of Object.keys(beforeUpgrade).filter(table => table !== 'migrations')) {
            if (JSON.stringify(beforeUpgrade[table]) !== JSON.stringify(afterUpgrade[table])) throw new Error(`Upgrade changed retained ${table} history.`);
        }
        console.log(fixture('check-upgrade'));
        const fingerprint = fixture('fingerprint');
        const dump = join(temporary, 'fixture.dump');
        postgres('pg_dump', ['--format=custom', '--no-owner', '--no-acl', '--file', dump, database]);
        fixture('create', restoredEnv); restoredCreated = true;
        const started = performance.now();
        postgres('pg_restore', ['--exit-on-error', '--no-owner', '--no-acl', '--dbname', restoredEnv.DB_DATABASE, dump]);
        if (fingerprint !== fixture('fingerprint', restoredEnv)) throw new Error('Restored row fingerprints differ.');
        console.log(fixture('check-upgrade', restoredEnv));
        console.log(`Representative upgrade and ${Object.keys(afterUpgrade).length}-table restore passed; restore took ${Math.round(performance.now() - started)} ms. Local PostgreSQL evidence only.`);
        status = 0;
    } else {
        server = spawn(php, ['-S', `127.0.0.1:${port}`, resolve(root, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
            { cwd: join(root, 'public'), env, windowsHide: true, stdio: 'ignore' });
        server.on('error', () => {});
        let ready = false;
        for (let i = 0; i < 80; i++) {
            if (interrupted) throw new Error('Isolated verification interrupted.');
            try { if ((await fetch(`${env.APP_URL}/up`, { signal: AbortSignal.timeout(1500) })).ok) { ready = true; break; } } catch {}
            if (server.exitCode !== null) break;
            await new Promise(resolve => setTimeout(resolve, 250));
        }
        if (!ready) throw new Error('The isolated browser server did not become healthy.');
        runner = spawn(process.execPath, ['node_modules/@playwright/test/cli.js', 'test', '--config=tests/Browser/playwright.config.mjs', ...process.argv.slice(2)],
            { cwd: root, env, stdio: 'inherit', windowsHide: true });
        runner.on('error', () => {});
        [status] = await once(runner, 'exit');
    }
} catch (error) {
    console.error(error.message);
} finally {
    if (server && server.exitCode === null) { const stopped = once(server, 'exit'); server.kill(); await stopped; }
    if (restoredCreated) { try { fixture('drop', restoredEnv); } catch (error) { console.error(error.message); status = 1; } }
    if (created) { try { fixture('drop'); } catch (error) { console.error(error.message); status = 1; } }
    await rm(temporary, { recursive: true, force: true });
}
process.exitCode = interrupted ? 130 : status ?? 1;
