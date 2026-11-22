import { readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { execFileSync } from 'node:child_process';

function collect(dir, out = []) {
    for (const entry of readdirSync(dir)) {
        const full = join(dir, entry);
        const info = statSync(full);
        if (info.isDirectory()) collect(full, out);
        else if (/\.(m?js)$/.test(entry)) out.push(full);
    }
    return out;
}

const files = [
    ...collect('resources/js'),
    ...collect('tests/Frontend'),
];

for (const file of files) {
    execFileSync(process.execPath, ['--check', file], { stdio: 'inherit' });
}

console.log(`Checked ${files.length} JavaScript files with zero syntax errors.`);
