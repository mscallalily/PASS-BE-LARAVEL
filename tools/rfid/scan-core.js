export function normalizeFrame(raw) {
  const tag = String(raw).trim().toUpperCase();
  return /^[A-F0-9]{24}$/.test(tag) ? tag : null;
}

// Time is monotonic in the browser. Repeated observations extend the quiet gap.
export class ScanGuard {
  constructor(now = () => performance.now()) { this.now = now; this.seen = new Map(); this.busy = false; }
  observe(raw) {
    const tag = normalizeFrame(raw);
    if (!tag) return { type: 'invalid' };
    const at = this.now(); const previous = this.seen.get(tag);
    this.seen.set(tag, at);
    for (const [key, time] of this.seen) if (at - time > 60000) this.seen.delete(key);
    if (this.seen.size > 128) this.seen.delete(this.seen.keys().next().value);
    if (previous !== undefined && at - previous < 3000) return { type: 'duplicate', tag };
    if (this.busy) return { type: 'busy', tag };
    this.busy = true;
    return { type: 'scan', tag };
  }
  complete() { this.busy = false; }
}
