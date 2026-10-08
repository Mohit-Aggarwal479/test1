// Canvas board renderer + chain animation.
import { TILE, idx } from './engine.js';

// Okabe-Ito colorblind-safe hues, direction is also encoded by the arrow glyph.
export const DIR_COLORS = ['#56B4E9', '#E69F00', '#009E73', '#CC79A7']; // U R D L
const SPLIT_COLOR = '#F0E442';
const BOMB_COLOR = '#D55E00';
const WALL_COLOR = '#2a2737';
const BURNT = '#221a1a';

function rr(ctx, x, y, w, h, r) {
  ctx.beginPath();
  ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r); ctx.arcTo(x + w, y + h, x, y + h, r);
  ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath();
}

/** Draw one tile (used by board and legend). x,y = top-left, s = size. */
export function drawTile(ctx, cell, x, y, s, { burnt = false, alpha = 1, selected = false, glow = 0 } = {}) {
  const pad = Math.max(1.5, s * 0.06);
  const r = s * 0.22;
  ctx.save();
  ctx.globalAlpha = alpha;
  if (cell.t === TILE.WALL) {
    rr(ctx, x + pad, y + pad, s - 2 * pad, s - 2 * pad, r);
    ctx.fillStyle = WALL_COLOR; ctx.fill();
    ctx.strokeStyle = '#3d3852'; ctx.lineWidth = Math.max(1, s * 0.04);
    ctx.save(); ctx.clip();
    for (let k = -s; k < s * 2; k += s * 0.22) { ctx.beginPath(); ctx.moveTo(x + k, y); ctx.lineTo(x + k - s, y + s); ctx.stroke(); }
    ctx.restore();
    ctx.restore();
    return;
  }
  if (burnt) {
    rr(ctx, x + pad, y + pad, s - 2 * pad, s - 2 * pad, r);
    const g = ctx.createRadialGradient(x + s / 2, y + s / 2, 1, x + s / 2, y + s / 2, s * 0.7);
    g.addColorStop(0, glow > 0 ? `rgba(255,${140 + 80 * glow | 0},40,${0.35 + 0.65 * glow})` : '#3a2418');
    g.addColorStop(1, BURNT);
    ctx.fillStyle = g; ctx.fill();
    ctx.restore();
    return;
  }
  const col = cell.t === TILE.SPLIT ? SPLIT_COLOR : cell.t === TILE.BOMB ? BOMB_COLOR : DIR_COLORS[cell.d];
  rr(ctx, x + pad, y + pad, s - 2 * pad, s - 2 * pad, r);
  ctx.fillStyle = shade(col, -0.55); ctx.fill();
  rr(ctx, x + pad, y + pad, s - 2 * pad, s - 2 * pad - s * 0.05, r);
  ctx.fillStyle = shade(col, -0.25); ctx.fill();
  if (selected) {
    ctx.lineWidth = Math.max(2, s * 0.08); ctx.strokeStyle = '#fff';
    rr(ctx, x + pad, y + pad, s - 2 * pad, s - 2 * pad, r); ctx.stroke();
  }
  ctx.translate(x + s / 2, y + s / 2);
  ctx.fillStyle = col; ctx.strokeStyle = col;
  const a = s * 0.28;
  if (cell.t === TILE.SPLIT) {
    // fork glyph: two perpendicular arrows
    ctx.lineWidth = s * 0.09; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(-a, 0); ctx.lineTo(a, 0); ctx.stroke();
    tri(ctx, a + s * 0.02, 0, s * 0.14, 0); tri(ctx, -a - s * 0.02, 0, s * 0.14, Math.PI);
    ctx.beginPath(); ctx.arc(0, 0, s * 0.09, 0, Math.PI * 2); ctx.fillStyle = '#fff'; ctx.fill();
  } else if (cell.t === TILE.BOMB) {
    ctx.beginPath(); ctx.arc(0, s * 0.03, s * 0.2, 0, Math.PI * 2); ctx.fill();
    ctx.lineWidth = s * 0.05; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(s * 0.1, -s * 0.12); ctx.quadraticCurveTo(s * 0.18, -s * 0.28, s * 0.3, -s * 0.24); ctx.stroke();
    ctx.fillStyle = '#ffe08a'; ctx.beginPath(); ctx.arc(s * 0.3, -s * 0.24, s * 0.05, 0, Math.PI * 2); ctx.fill();
    // small direction pip so the bomb's continuation is readable
    ctx.rotate(cell.d * Math.PI / 2);
    ctx.fillStyle = '#fff'; tri(ctx, 0, -s * 0.36, s * 0.09, -Math.PI / 2);
  } else {
    ctx.rotate(cell.d * Math.PI / 2);
    ctx.lineWidth = s * 0.11; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(0, a); ctx.lineTo(0, -a * 0.3); ctx.stroke();
    tri(ctx, 0, -a * 0.45, s * 0.2, -Math.PI / 2);
  }
  ctx.restore();
}
function tri(ctx, x, y, s, ang) {
  ctx.save(); ctx.translate(x, y); ctx.rotate(ang);
  ctx.beginPath(); ctx.moveTo(s, 0); ctx.lineTo(-s * 0.6, -s * 0.85); ctx.lineTo(-s * 0.6, s * 0.85); ctx.closePath(); ctx.fill();
  ctx.restore();
}
function shade(hex, amt) {
  const n = parseInt(hex.slice(1), 16);
  let r = n >> 16, g = (n >> 8) & 255, b = n & 255;
  const f = amt < 0 ? 0 : 255, t = Math.abs(amt);
  r = Math.round((f - r) * t + r); g = Math.round((f - g) * t + g); b = Math.round((f - b) * t + b);
  return `rgb(${r},${g},${b})`;
}

export class BoardView {
  constructor(canvas, { onBurn, onBomb, onEnd } = {}) {
    this.cv = canvas;
    this.ctx = canvas.getContext('2d');
    this.board = null;
    this.burnt = [];
    this.glow = [];      // per-cell glow 0..1 after burning
    this.fall = [];      // per-cell y offset (in tiles) for gravity animation
    this.selected = null;
    this.particles = [];
    this.trails = [];
    this.shake = 0;
    this.reduced = false;
    this.hooks = { onBurn, onBomb, onEnd };
    this.overlay = null; // {r,c,text}
    this.pulse = null;   // {r,c} tutorial pulse
    this.dim = false;
    this._raf = 0;
    this._last = 0;
    this.running = false;
    this.anim = null;
  }

  setBoard(board, burnt) {
    this.board = board;
    const N = board.n * board.n;
    this.burnt = burnt ? burnt.slice() : new Array(N).fill(false);
    this.glow = new Array(N).fill(0);
    this.fall = new Array(N).fill(0);
    this.selected = null; this.overlay = null;
    this.layout();
  }

  layout() {
    const wrap = this.cv.parentElement;
    if (!wrap || !this.board) return;
    const w = wrap.clientWidth, h = wrap.clientHeight;
    const size = Math.max(120, Math.floor(Math.min(w, h, 560)));
    const dpr = Math.min(window.devicePixelRatio || 1, 3);
    this.size = size; this.dpr = dpr;
    this.cv.style.width = size + 'px'; this.cv.style.height = size + 'px';
    this.cv.width = Math.round(size * dpr); this.cv.height = Math.round(size * dpr);
    this.tile = size / this.board.n;
  }

  hit(clientX, clientY) {
    const rect = this.cv.getBoundingClientRect();
    const x = clientX - rect.left, y = clientY - rect.top;
    const c = Math.floor(x / this.tile), r = Math.floor(y / this.tile);
    if (r < 0 || c < 0 || r >= this.board.n || c >= this.board.n) return null;
    return { r, c };
  }

  start() {
    if (this.running) return;
    this.running = true;
    const loop = (t) => {
      if (!this.running) return;
      const dt = Math.min(50, t - (this._last || t)); this._last = t;
      this.update(dt, t);
      this.draw(t);
      this._raf = requestAnimationFrame(loop);
    };
    this._raf = requestAnimationFrame(loop);
  }
  stop() { this.running = false; cancelAnimationFrame(this._raf); this._last = 0; }

  center(r, c) { return { x: (c + 0.5) * this.tile, y: (r + 0.5) * this.tile }; }

  burst(r, c, color, count) {
    if (this.reduced) count = Math.ceil(count / 3);
    const { x, y } = this.center(r, c);
    for (let k = 0; k < count && this.particles.length < 500; k++) {
      const a = Math.random() * Math.PI * 2, sp = (0.04 + Math.random() * 0.16) * this.tile;
      this.particles.push({ x, y, vx: Math.cos(a) * sp, vy: Math.sin(a) * sp - 0.05 * this.tile, life: 1, decay: 0.012 + Math.random() * 0.02, size: (0.04 + Math.random() * 0.08) * this.tile, color });
    }
  }

  /**
   * Animate a simulation result. Resolves when finished.
   * events: from engine.simulate; speed: 1 normal, >1 faster.
   */
  play(events, { speed = 1 } = {}) {
    return new Promise((resolve) => {
      // timeline: step k happens at T(k); steps accelerate as the chain grows
      const times = [0];
      let maxStep = 0;
      for (const e of events) maxStep = Math.max(maxStep, e.step);
      for (let k = 1; k <= maxStep + 1; k++) times[k] = times[k - 1] + Math.max(38, 120 - k * 6) / speed;
      const queue = events.map((e) => ({ ...e, t: times[e.step] + (e.bomb ? 60 / speed : 0) })).sort((a, b) => a.t - b.t);
      this.anim = { queue, t0: performance.now(), burnN: 0, resolve, end: times[maxStep + 1] + 450 / speed };
      this.start();
    });
  }

  /** Gravity animation for endless mode. moves from engine.collapse. */
  dropIn(newBoard, moves) {
    const n = newBoard.n;
    this.board = newBoard;
    this.burnt = new Array(n * n).fill(false);
    this.glow = new Array(n * n).fill(0);
    this.fall = new Array(n * n).fill(0);
    for (const m of moves) {
      const i = idx(n, m.to.r, m.to.c);
      this.fall[i] = m.fresh ? m.to.r - m.spawnRow + 0.0 : m.to.r - m.from.r;
    }
    this.fallV = new Array(n * n).fill(0);
    return new Promise((res) => setTimeout(res, 420));
  }

  update(dt, now) {
    // chain animation
    if (this.anim) {
      const el = now - this.anim.t0;
      const q = this.anim.queue;
      while (q.length && q[0].t <= el) {
        const e = q.shift();
        if (e.type === 'burn') {
          const i = idx(this.board.n, e.r, e.c);
          this.burnt[i] = true; this.glow[i] = 1;
          const col = e.tile === TILE.BOMB ? BOMB_COLOR : e.tile === TILE.SPLIT ? SPLIT_COLOR : '#ffb347';
          this.burst(e.r, e.c, col, e.tile === TILE.BOMB ? 26 : 10);
          if (!this.reduced) this.shake = Math.min(this.tile * 0.35, this.shake + this.tile * (e.tile === TILE.BOMB ? 0.25 : 0.03));
          this.hooks.onBurn?.(this.anim.burnN++, e);
          if (e.tile === TILE.BOMB && !e.bomb) this.hooks.onBomb?.(e);
        } else if (e.type === 'fly') {
          this.trails.push({ a: this.center(e.from.r, e.from.c), b: this.center(e.r, e.c), life: 1 });
        } else if (e.type === 'die') {
          const a = this.center(e.from.r, e.from.c);
          const b = this.center(e.r, e.c);
          const mid = { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
          this.trails.push({ a, b: mid, life: 0.6 });
          this.burst(mid.y / this.tile - 0.5, mid.x / this.tile - 0.5, '#888', 4);
        }
      }
      if (!q.length && el >= this.anim.end) {
        const r = this.anim.resolve; this.anim = null; this.hooks.onEnd?.(); r();
      }
    }
    // gravity
    if (this.fallV) {
      let any = false;
      for (let i = 0; i < this.fall.length; i++) {
        if (this.fall[i] > 0) {
          this.fallV[i] += dt * 0.00012 * 60;
          this.fall[i] = Math.max(0, this.fall[i] - this.fallV[i] * dt / 16);
          any = true;
        }
      }
      if (!any) this.fallV = null;
    }
    for (let i = 0; i < this.glow.length; i++) if (this.glow[i] > 0) this.glow[i] = Math.max(0, this.glow[i] - dt / 900);
    for (const p of this.particles) { p.x += p.vx * dt / 16; p.y += p.vy * dt / 16; p.vy += 0.006 * this.tile * dt / 16; p.life -= p.decay * dt / 16; }
    this.particles = this.particles.filter((p) => p.life > 0);
    for (const t of this.trails) t.life -= dt / 500;
    this.trails = this.trails.filter((t) => t.life > 0);
    this.shake *= Math.pow(0.86, dt / 16);
    if (this.shake < 0.2) this.shake = 0;
  }

  draw(now) {
    const { ctx, board } = this;
    if (!board) return;
    const s = this.tile, n = board.n;
    ctx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);
    ctx.clearRect(0, 0, this.size, this.size);
    ctx.save();
    if (this.shake) ctx.translate((Math.random() - 0.5) * this.shake, (Math.random() - 0.5) * this.shake);
    for (let r = 0; r < n; r++) for (let c = 0; c < n; c++) {
      const i = r * n + c;
      const sel = this.selected && this.selected.r === r && this.selected.c === c;
      let alpha = this.dim && !this.burnt[i] ? 0.35 : 1;
      const yOff = -this.fall[i] * s;
      if (yOff < -s * n) continue;
      drawTile(ctx, board.cells[i], c * s, r * s + yOff, s, { burnt: this.burnt[i], glow: this.glow[i], selected: sel, alpha });
    }
    // tutorial / hint pulse
    if (this.pulse) {
      const p = 0.5 + 0.5 * Math.sin(now / 180);
      ctx.lineWidth = 3 + p * 3; ctx.strokeStyle = `rgba(255,224,138,${0.5 + p * 0.5})`;
      rr(ctx, this.pulse.c * s + 2, this.pulse.r * s + 2, s - 4, s - 4, s * 0.22); ctx.stroke();
    }
    // trails
    ctx.lineCap = 'round';
    for (const t of this.trails) {
      ctx.strokeStyle = `rgba(255,200,90,${t.life})`; ctx.lineWidth = s * 0.12 * t.life + 1;
      ctx.shadowColor = '#ff8a1f'; ctx.shadowBlur = 12;
      ctx.beginPath(); ctx.moveTo(t.a.x, t.a.y); ctx.lineTo(t.b.x, t.b.y); ctx.stroke();
    }
    ctx.shadowBlur = 0;
    // particles
    for (const p of this.particles) {
      ctx.globalAlpha = Math.max(0, p.life); ctx.fillStyle = p.color;
      ctx.beginPath(); ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2); ctx.fill();
    }
    ctx.globalAlpha = 1;
    // overlay badge (preview count etc.)
    if (this.overlay) {
      const { x, y } = this.center(this.overlay.r, this.overlay.c);
      const txt = this.overlay.text;
      ctx.font = `800 ${Math.round(s * 0.42)}px system-ui, sans-serif`;
      const w = ctx.measureText(txt).width + s * 0.4, h = s * 0.62;
      const by = Math.max(h / 2 + 2, y - s * 0.85);
      const bx = Math.min(this.size - w / 2 - 2, Math.max(w / 2 + 2, x));
      ctx.fillStyle = '#ffe08a'; rr(ctx, bx - w / 2, by - h / 2, w, h, h / 2); ctx.fill();
      ctx.fillStyle = '#1a0e00'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText(txt, bx, by + 1);
    }
    ctx.restore();
  }
}

/** Static render (share cards, map thumbnails). burnt may be undefined. */
export function renderBoardImage(board, burnt, size, opts = {}) {
  const cv = document.createElement('canvas');
  cv.width = size; cv.height = size;
  const ctx = cv.getContext('2d');
  const s = size / board.n;
  for (let r = 0; r < board.n; r++) for (let c = 0; c < board.n; c++) {
    const i = r * board.n + c;
    drawTile(ctx, board.cells[i], c * s, r * s, s, { burnt: burnt ? burnt[i] : false, glow: opts.glow && burnt && burnt[i] ? 0.6 : 0 });
  }
  return cv;
}

/** Tiny silhouette for the monthly map from a stored mask string ('0' unburnt, '1' burnt, '2' wall). */
export function drawSilhouette(canvas, mask) {
  const n = Math.round(Math.sqrt(mask.length));
  const W = canvas.width = 64, H = canvas.height = 64;
  const ctx = canvas.getContext('2d');
  const s = W / n;
  ctx.fillStyle = '#1a1722'; ctx.fillRect(0, 0, W, H);
  for (let i = 0; i < mask.length; i++) {
    const r = Math.floor(i / n), c = i % n;
    if (mask[i] === '1') { ctx.fillStyle = '#ff8a1f'; ctx.fillRect(c * s + 0.5, r * s + 0.5, s - 1, s - 1); }
    else if (mask[i] === '2') { ctx.fillStyle = '#2f2b3d'; ctx.fillRect(c * s + 0.5, r * s + 0.5, s - 1, s - 1); }
  }
}
