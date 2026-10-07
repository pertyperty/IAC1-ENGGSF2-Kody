import test from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
// Resolve the dependency actually used by the development process runner.
const { quote } = createRequire(require.resolve('concurrently'))('shell-quote');

test('development command quoting rejects line terminators following a comment token', () => {
    for (const terminator of ['\n', '\r', '\u2028', '\u2029']) {
        assert.throws(() => quote(['echo', { comment: 'example' }, `text${terminator}another-command`]), TypeError);
    }
    // No quoted strings are executed by this regression.
    assert.equal(quote(['echo', 'hello world']), "echo 'hello world'");
});
