// FUSE engine: pure, deterministic, no DOM. Board generation, spark simulation,
// optimal solver, endless gravity/refill. Everything integer-based so a daily
// board + result is bit-identical on every device.

// ---------- PRNG ----------
export function mulberry32(seed) {
  let a = seed >>> 0;
  return function () {
    a = (a + 0x6D2B79F5) >>> 0;
    let t = a;
    t = Math.imul(t ^ (t >>> 15), t | 1);
    t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

// ---------- Constants ----------
export const DIR = { U: 0, R: 1, D: 2, L: 3 };
export const DR = [-1, 0, 1, 0];
export const DC = [0, 1, 0, -1];
export const TILE = { ARROW: 0, SPLIT: 1, BOMB: 2, WALL: 3 };

// Daily #1 is this date (local time). Day index = days since epoch + 1.
export const DAILY_EPOCH = { y: 2026, m: 10, d: 1 };

export function dateKey(date = new Date()) {
  return date.getFullYear() * 10000 + (date.getMonth() + 1) * 100 + date.getDate();
}
export function dailyNumber(date = new Date()) {
  const a = Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());
  const b = Date.UTC(DAILY_EPOCH.y, DAILY_EPOCH.m - 1, DAILY_EPOCH.d);
  return Math.round((a - b) / 86400000) + 1;
}
export function dailySize(date = new Date()) {
  const wd = date.getDay(); // 0 Sun, 6 Sat
  return wd === 0 || wd === 6 ? 8 : 6;
}
export function dailySeed(date = new Date()) {
  // mix the date key so consecutive days are not correlated
  return (dateKey(date) * 2654435761) >>> 0;
}

// ---------- Board ----------
// cells[i] = { t: TILE, d: DIR }  (walls have d but it is unused)
export function makeBoard(n, cells) {
  return { n, cells };
}
export const idx = (n, r, c) => r * n + c;
export const inb = (n, r, c) => r >= 0 && c >= 0 && r < n && c < n;

const DENSITY = { split: 0.09, bomb: 0.045, wall: 0.06 };

export function randomBoard(n, rng, density = DENSITY) {
  const cells = new Array(n * n);
  for (let i = 0; i < n * n; i++) {
    const x = rng();
    let t = TILE.ARROW;
    if (x < density.wall) t = TILE.WALL;
    else if (x < density.wall + density.bomb) t = TILE.BOMB;
    else if (x < density.wall + density.bomb + density.split) t = TILE.SPLIT;
    cells[i] = { t, d: Math.floor(rng() * 4) };
  }
  return makeBoard(n, cells);
}

/** Generate a board whose optimal chain burns 50%..85% of burnable tiles. */
export function generateBoard(n, seed, opts = {}) {
  const rng = mulberry32(seed);
  const lo = opts.minFrac ?? 0.50, hi = opts.maxFrac ?? 0.85;
  let board = null, best = null;
  for (let attempt = 0; attempt < 200; attempt++) {
    board = randomBoard(n, rng);
    best = solve(board);
    const burnable = board.cells.filter(c => c.t !== TILE.WALL).length;
    const frac = best.count / burnable;
    if (frac >= lo && frac <= hi) break;
  }
  return { board, optimal: best };
}

// ---------- Simulation ----------
/**
 * Ignite tile (r,c). Returns ordered events for animation plus the burnt set.
 * Rules:
 *  - Tapping a tile burns it and fires a spark in its arrow direction.
 *  - A spark flies in a straight line over already-burnt tiles. Off-board or a WALL ends it.
 *  - Landing on an unburnt ARROW burns it and the spark continues in that arrow's direction.
 *  - SPLIT burns and emits two sparks perpendicular to the incoming direction.
 *  - BOMB burns, also burns its 3x3 neighbourhood (walls excluded, those tiles do NOT fire),
 *    then the spark continues in the bomb's own arrow direction.
 * Sparks advance in lock-step rounds so animation timing (`step`) is well defined.
 */
export function simulate(board, r0, c0, burntIn) {
  const { n, cells } = board;
  const burnt = burntIn ? burntIn.slice() : new Array(n * n).fill(false);
  const events = []; // {type:'burn'|'fly'|'die', r, c, step, from?, bomb?}
  const start = idx(n, r0, c0);
  if (!inb(n, r0, c0) || cells[start].t === TILE.WALL || burnt[start]) {
    return { events, burnt, count: 0 };
  }
  let count = 0;
  const burn = (i, step, extra) => {
    if (burnt[i]) return;
    burnt[i] = true; count++;
    events.push({ type: 'burn', r: Math.floor(i / n), c: i % n, step, tile: cells[i].t, ...extra });
  };
  const enterTile = (i, step, sparks, fromDir) => {
    // i is unburnt & not wall
    const cell = cells[i];
    const r = Math.floor(i / n), c = i % n;
    burn(i, step);
    if (cell.t === TILE.BOMB) {
      for (let dr = -1; dr <= 1; dr++) for (let dc = -1; dc <= 1; dc++) {
        if (!dr && !dc) continue;
        const rr = r + dr, cc = c + dc;
        if (!inb(n, rr, cc)) continue;
        const j = idx(n, rr, cc);
        if (cells[j].t !== TILE.WALL) burn(j, step, { bomb: true });
      }
      sparks.push({ r, c, d: cell.d });
    } else if (cell.t === TILE.SPLIT) {
      const a = (fromDir + 1) & 3, b = (fromDir + 3) & 3;
      sparks.push({ r, c, d: a }, { r, c, d: b });
    } else {
      sparks.push({ r, c, d: cell.d });
    }
  };

  let sparks = [];
  // a tapped splitter fires left/right, matching its glyph
  enterTile(start, 0, sparks, cells[start].t === TILE.SPLIT ? DIR.U : cells[start].d);
  let step = 1;
  while (sparks.length && step < 4096) {
    const next = [];
    for (const s of sparks) {
      // fly over burnt tiles until the next unburnt tile; walls and edges kill the spark
      let r = s.r + DR[s.d], c = s.c + DC[s.d], hit = -1;
      while (inb(n, r, c)) {
        const j = idx(n, r, c);
        if (cells[j].t === TILE.WALL) break;
        if (!burnt[j]) { hit = j; break; }
        r += DR[s.d]; c += DC[s.d];
      }
      if (hit < 0) { events.push({ type: 'die', r, c, step, from: { r: s.r, c: s.c } }); continue; }
      events.push({ type: 'fly', r, c, step, from: { r: s.r, c: s.c } });
      enterTile(hit, step, next, s.d);
    }
    sparks = next;
    step++;
  }
  return { events, burnt, count, steps: step };
}

/** Try every start tile; return the best (ties -> lowest index for determinism). */
export function solve(board, burntIn) {
  const { n, cells } = board;
  let best = { count: -1, r: 0, c: 0 };
  const scores = new Array(n * n).fill(0);
  for (let i = 0; i < n * n; i++) {
    if (cells[i].t === TILE.WALL || (burntIn && burntIn[i])) { scores[i] = -1; continue; }
    const r = Math.floor(i / n), c = i % n;
    const res = simulate(board, r, c, burntIn);
    scores[i] = res.count;
    if (res.count > best.count) best = { count: res.count, r, c };
  }
  return { ...best, scores };
}

/** Rank (0..1, 1 = best) of a score among all possible starts on this board. */
export function percentileOnBoard(scores, score) {
  const valid = scores.filter(s => s >= 0);
  if (!valid.length) return 0;
  const worse = valid.filter(s => s < score).length;
  return worse / valid.length;
}

// ---------- Endless: remove burnt, gravity, refill ----------
/** Returns new board + per-cell fall info for animation. Burnt tiles vanish, columns fall, new tiles drop in. */
export function collapse(board, burnt, rng) {
  const { n, cells } = board;
  const out = new Array(n * n);
  const moves = []; // {from:{r,c}, to:{r,c}} for survivors; {to:{r,c}, fresh:true, spawnRow:-k}
  for (let c = 0; c < n; c++) {
    let write = n - 1;
    for (let r = n - 1; r >= 0; r--) {
      const i = idx(n, r, c);
      if (!burnt[i]) {
        out[idx(n, write, c)] = cells[i];
        if (write !== r) moves.push({ from: { r, c }, to: { r: write, c } });
        write--;
      }
    }
    let k = 1;
    for (let r = write; r >= 0; r--) {
      const x = rng();
      let t = TILE.ARROW;
      if (x < DENSITY.wall * 0.5) t = TILE.WALL; // fewer walls in endless refill
      else if (x < DENSITY.wall * 0.5 + DENSITY.bomb) t = TILE.BOMB;
      else if (x < DENSITY.wall * 0.5 + DENSITY.bomb + DENSITY.split) t = TILE.SPLIT;
      out[idx(n, r, c)] = { t, d: Math.floor(rng() * 4) };
      moves.push({ to: { r, c }, fresh: true, spawnRow: -k });
      k++;
    }
  }
  return { board: makeBoard(n, out), moves };
}

// ---------- Share helpers ----------
/** Emoji silhouette of burnt tiles; does not reveal the tapped tile. */
export function emojiSilhouette(board, burnt, { burntEmoji = '\u{1F7E7}', emptyEmoji = '\u2B1B', wallEmoji = '\u2B1C' } = {}) {
  const { n, cells } = board;
  const rows = [];
  for (let r = 0; r < n; r++) {
    let s = '';
    for (let c = 0; c < n; c++) {
      const i = idx(n, r, c);
      s += burnt[i] ? burntEmoji : cells[i].t === TILE.WALL ? wallEmoji : emptyEmoji;
    }
    rows.push(s);
  }
  return rows.join('\n');
}

/** Compact serialization for save files / debugging. */
export function encodeBoard(board) {
  return board.n + ':' + board.cells.map(c => c.t * 4 + c.d).map(v => v.toString(16)).join('');
}
export function decodeBoard(s) {
  const [n, body] = s.split(':');
  const cells = [...body].map(h => { const v = parseInt(h, 16); return { t: v >> 2, d: v & 3 }; });
  return makeBoard(+n, cells);
}
