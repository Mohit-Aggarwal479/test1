import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
  mulberry32, generateBoard, simulate, solve, collapse, makeBoard, emojiSilhouette,
  dailySeed, dailySize, dailyNumber, dateKey, percentileOnBoard, encodeBoard, decodeBoard, TILE, DIR,
} from '../src/engine.js';

const A = (d) => ({ t: TILE.ARROW, d });
const S = (d = 0) => ({ t: TILE.SPLIT, d });
const B = (d) => ({ t: TILE.BOMB, d });
const W = { t: TILE.WALL, d: 0 };
const burntList = (res) => res.events.filter((e) => e.type === 'burn').map((e) => [e.r, e.c]);

test('mulberry32 is deterministic and in [0,1)', () => {
  const a = mulberry32(42), b = mulberry32(42);
  for (let i = 0; i < 1000; i++) {
    const x = a();
    assert.equal(x, b());
    assert.ok(x >= 0 && x < 1);
  }
});

test('daily board is identical across calls (determinism)', () => {
  const d = new Date(2026, 9, 25);
  const g1 = generateBoard(dailySize(d), dailySeed(d));
  const g2 = generateBoard(dailySize(d), dailySeed(d));
  assert.equal(encodeBoard(g1.board), encodeBoard(g2.board));
  assert.deepEqual([g1.optimal.count, g1.optimal.r, g1.optimal.c], [g2.optimal.count, g2.optimal.r, g2.optimal.c]);
});

test('daily size: 8x8 on weekends, 6x6 on weekdays', () => {
  assert.equal(dailySize(new Date(2026, 9, 10)), 8); // Saturday
  assert.equal(dailySize(new Date(2026, 9, 11)), 8); // Sunday
  assert.equal(dailySize(new Date(2026, 9, 12)), 6); // Monday
});

test('daily numbering starts at epoch and is DST safe', () => {
  assert.equal(dailyNumber(new Date(2026, 9, 1)), 1);
  assert.equal(dailyNumber(new Date(2026, 9, 2, 23, 59)), 2);
  // spans the late-October / March DST changes in many regions
  assert.equal(dailyNumber(new Date(2026, 10, 1)), 32);
  assert.equal(dailyNumber(new Date(2027, 3, 1)), 183);
  assert.equal(dateKey(new Date(2026, 0, 5)), 20260105);
});

test('arrow chain follows directions and flies over burnt tiles', () => {
  // row 0: R R D ; row 1: U L L ; row 2: walls
  const b = makeBoard(3, [A(DIR.R), A(DIR.R), A(DIR.D), A(DIR.U), A(DIR.L), A(DIR.L), W, W, W]);
  const res = simulate(b, 0, 0);
  assert.deepEqual(burntList(res), [[0, 0], [0, 1], [0, 2], [1, 2], [1, 1], [1, 0]]);
  assert.equal(res.count, 6);
});

test('wall stops the spark; tapping a wall burns nothing', () => {
  const b = makeBoard(3, [A(DIR.R), W, A(DIR.L), A(0), A(0), A(0), A(0), A(0), A(0)]);
  assert.equal(simulate(b, 0, 0).count, 1);
  assert.equal(simulate(b, 0, 1).count, 0);
});

test('tapped splitter fires left/right (matching its glyph) regardless of stored d', () => {
  for (let d = 0; d < 4; d++) {
    const cells = Array.from({ length: 9 }, () => A(DIR.U));
    cells[4] = S(d);
    const res = simulate(makeBoard(3, cells), 1, 1);
    const burnt = burntList(res).map(String);
    assert.ok(burnt.includes('1,0') && burnt.includes('1,2'), `d=${d} should fire L/R`);
  }
});

test('bomb burns its 3x3 (not walls) and continues in its own direction', () => {
  const cells = [A(0), W, A(0), A(0), B(DIR.D), A(0), A(0), A(0), A(0)];
  const res = simulate(makeBoard(3, cells), 1, 1);
  assert.equal(res.burnt[1], false); // wall untouched
  assert.equal(res.count, 8);
});

test('solver finds the maximum over all starts', () => {
  const g = generateBoard(6, 12345);
  let best = -1;
  for (let r = 0; r < 6; r++) for (let c = 0; c < 6; c++) best = Math.max(best, simulate(g.board, r, c).count);
  assert.equal(g.optimal.count, best);
  assert.equal(simulate(g.board, g.optimal.r, g.optimal.c).count, best);
});

test('generated boards stay within the quality band', () => {
  for (let s = 1; s <= 300; s++) for (const n of [6, 8]) {
    const g = generateBoard(n, s * 7919);
    const burnable = g.board.cells.filter((c) => c.t !== TILE.WALL).length;
    const f = g.optimal.count / burnable;
    assert.ok(f >= 0.5 && f <= 0.85, `seed ${s} n ${n} frac ${f}`);
  }
});

test('collapse keeps the board full and preserves survivors in column order', () => {
  const g = generateBoard(7, 99);
  const res = simulate(g.board, g.optimal.r, g.optimal.c);
  const col = collapse(g.board, res.burnt, mulberry32(1));
  assert.equal(col.board.cells.length, 49);
  assert.ok(col.board.cells.every((c) => c && c.t >= 0 && c.t <= 3 && c.d >= 0 && c.d <= 3));
  for (let c = 0; c < 7; c++) {
    const survivors = [];
    for (let r = 0; r < 7; r++) if (!res.burnt[r * 7 + c]) survivors.push(g.board.cells[r * 7 + c]);
    const bottom = [];
    for (let r = 7 - survivors.length; r < 7; r++) bottom.push(col.board.cells[r * 7 + c]);
    assert.deepEqual(bottom, survivors);
  }
});

test('percentile and emoji silhouette', () => {
  assert.equal(percentileOnBoard([1, 2, 3, -1], 3), 2 / 3);
  const b = makeBoard(2, [A(1), W, A(0), A(0)]);
  const sil = emojiSilhouette(b, [true, false, false, false]);
  assert.equal(sil, '\u{1F7E7}⬜\n⬛⬛');
});

test('board encode/decode roundtrip', () => {
  const g = generateBoard(8, 777);
  assert.equal(encodeBoard(decodeBoard(encodeBoard(g.board))), encodeBoard(g.board));
});

test('performance: 8x8 generate+solve under 5ms average', () => {
  const t = performance.now();
  for (let s = 0; s < 200; s++) generateBoard(8, s * 31 + 1);
  assert.ok((performance.now() - t) / 200 < 5);
});
