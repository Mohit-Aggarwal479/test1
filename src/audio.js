// Synthesized sound: no asset files. Pops rise in pitch along a chain.
let ctx = null;
let master = null;
let enabled = true;

function ensure() {
  if (ctx) return ctx;
  try {
    const AC = window.AudioContext || window.webkitAudioContext;
    if (!AC) return null;
    ctx = new AC();
    master = ctx.createGain();
    master.gain.value = 0.5;
    master.connect(ctx.destination);
  } catch { ctx = null; }
  return ctx;
}

export const sound = {
  setEnabled(v) { enabled = !!v; },
  get enabled() { return enabled; },
  unlock() { const c = ensure(); if (c && c.state === 'suspended') c.resume().catch(() => {}); },

  pop(chainIndex = 0, kind = 0) {
    if (!enabled) return;
    const c = ensure(); if (!c) return;
    const t = c.currentTime;
    // pentatonic climb so long chains stay musical
    const scale = [0, 2, 4, 7, 9];
    const step = chainIndex % scale.length, oct = Math.floor(chainIndex / scale.length);
    const semis = scale[step] + 12 * Math.min(oct, 3);
    const f = 330 * Math.pow(2, semis / 12);
    const o = c.createOscillator(), g = c.createGain();
    o.type = kind === 2 ? 'sawtooth' : kind === 1 ? 'square' : 'triangle';
    o.frequency.setValueAtTime(f * 1.5, t);
    o.frequency.exponentialRampToValueAtTime(f, t + 0.05);
    g.gain.setValueAtTime(0.0001, t);
    g.gain.exponentialRampToValueAtTime(kind === 2 ? 0.22 : 0.16, t + 0.008);
    g.gain.exponentialRampToValueAtTime(0.0001, t + 0.16);
    o.connect(g); g.connect(master);
    o.start(t); o.stop(t + 0.18);
  },

  boom() {
    if (!enabled) return;
    const c = ensure(); if (!c) return;
    const t = c.currentTime;
    const len = Math.floor(c.sampleRate * 0.35);
    const buf = c.createBuffer(1, len, c.sampleRate);
    const d = buf.getChannelData(0);
    for (let i = 0; i < len; i++) d[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / len, 2.5);
    const src = c.createBufferSource(); src.buffer = buf;
    const lp = c.createBiquadFilter(); lp.type = 'lowpass'; lp.frequency.value = 900;
    const g = c.createGain(); g.gain.value = 0.5;
    src.connect(lp); lp.connect(g); g.connect(master);
    src.start(t);
  },

  thud() {
    if (!enabled) return;
    const c = ensure(); if (!c) return;
    const t = c.currentTime;
    const o = c.createOscillator(), g = c.createGain();
    o.type = 'sine';
    o.frequency.setValueAtTime(140, t);
    o.frequency.exponentialRampToValueAtTime(50, t + 0.2);
    g.gain.setValueAtTime(0.3, t);
    g.gain.exponentialRampToValueAtTime(0.0001, t + 0.25);
    o.connect(g); g.connect(master); o.start(t); o.stop(t + 0.3);
  },

  fanfare(perfect = false) {
    if (!enabled) return;
    const c = ensure(); if (!c) return;
    const notes = perfect ? [523, 659, 784, 1047, 1319] : [523, 659, 784];
    notes.forEach((f, i) => {
      const t = c.currentTime + i * 0.09;
      const o = c.createOscillator(), g = c.createGain();
      o.type = 'triangle'; o.frequency.value = f;
      g.gain.setValueAtTime(0.0001, t);
      g.gain.exponentialRampToValueAtTime(0.18, t + 0.01);
      g.gain.exponentialRampToValueAtTime(0.0001, t + 0.35);
      o.connect(g); g.connect(master); o.start(t); o.stop(t + 0.4);
    });
  },

  tick() {
    if (!enabled) return;
    const c = ensure(); if (!c) return;
    const t = c.currentTime;
    const o = c.createOscillator(), g = c.createGain();
    o.type = 'sine'; o.frequency.value = 880;
    g.gain.setValueAtTime(0.06, t);
    g.gain.exponentialRampToValueAtTime(0.0001, t + 0.04);
    o.connect(g); g.connect(master); o.start(t); o.stop(t + 0.05);
  },
};
