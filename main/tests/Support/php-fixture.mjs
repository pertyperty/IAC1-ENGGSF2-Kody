import { execFileSync } from 'node:child_process';

// An explicit executable also works on Windows, where Node cannot run php.bat.
export function phpFixture(args, options = {}) {
    const executable = process.env.KODY_TEST_PHP || 'php';
    try {
        const output = execFileSync(executable, args, { ...options, encoding: 'utf8', windowsHide: true });
        JSON.parse(output);
        return output;
    } catch {
        throw new Error('PHP fixture failed or returned invalid JSON. Set KODY_TEST_PHP to a working PHP 8.4+ executable and check its extensions with php --ini and php -m.');
    }
}
