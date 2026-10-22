import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
const source = await readFile(new URL('./scan-core.js', import.meta.url), 'utf8');
const { normalizeFrame, ScanGuard } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
const tag = 'A'.repeat(24), other = 'B'.repeat(24);
test('HID frames require one complete EPC and keep leading zeroes', () => {
  assert.equal(normalizeFrame(' 0' + 'a'.repeat(23) + '\r\n'), '0' + 'A'.repeat(23));
  for (const value of ['', tag + tag, 'v' + tag, tag.slice(1), tag + 'G', '<script>']) assert.equal(normalizeFrame(value), null);
});
test('continuous reads extend the quiet interval rather than resubmitting every three seconds', () => {
  let at = 0; const guard = new ScanGuard(() => at);
  assert.equal(guard.observe(tag).type, 'scan'); guard.complete();
  for (at = 100; at < 10000; at += 100) assert.equal(guard.observe(tag).type, 'duplicate');
  at = 12900; assert.equal(guard.observe(tag).type, 'scan');
});
test('only one request is active and another tag is never queued', () => {
  let at = 0; const guard = new ScanGuard(() => at);
  assert.equal(guard.observe(tag).type, 'scan');
  assert.equal(guard.observe(other).type, 'busy');
  guard.complete(); at = 20; assert.equal(guard.observe(other).type, 'duplicate');
  at = 3020; assert.equal(guard.observe(other).type, 'scan');
});
test('completion and direction changes do not clear the held-tag cache', () => {
  let at = 0; const guard = new ScanGuard(() => at);
  assert.equal(guard.observe(tag).type, 'scan'); guard.complete();
  at = 2000; assert.equal(guard.observe(tag).type, 'duplicate');
  at = 4999; assert.equal(guard.observe(tag).type, 'duplicate');
  at = 7999; assert.equal(guard.observe(tag).type, 'scan');
});
