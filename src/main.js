import {
  generateBoard, simulate, solve, collapse, mulberry32, emojiSilhouette, percentileOnBoard,
  dailySeed, dailySize, dailyNumber, dateKey, TILE, makeBoard, idx,
} from './engine.js';
import { BoardView, drawTile, renderBoardImage, drawSilhouette } from './view.js';
import { sound } from './audio.js';
import { storage, haptics, share, setupSystemUI, ads, isNative } from './platform.js';
import { Filesystem, Directory } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';
import { App } from '@capacitor/app';

const STORE_URL = 'https://play.google.com/store/apps/details?id=com.mohitaggarwal.randomapp';
const ENDLESS_N = 7;
const ENDLESS_TAPS = 10;
const BIG_CHAIN = 12; // a chain this long refunds the tap

// ---------------------------------------------------------------- save
const DEFAULT_SAVE = {
  v: 1,
  daily: {},            // dateKey -> { s: score, b: best, m: mask, t: [r,c], pr: practiced }
  streak: { count: 0, best: 0, last: 0, freezes: 0 },
  endless: { best: 0, week: 0, weekKey: '', rounds: 0 },
  stats: { perfects: 0, dailies: 0, gapSum: 0, biggest: 0, sessions: 0 },
  repairs: {},          // 'YYYYMM' -> count used
  settings: { sound: true, haptics: true, reduced: false },
  onboarded: false,
};
let save = structuredClone(DEFAULT_SAVE);
async function load() {
  const s = await storage.get('fuse.save', null);
  if (s && s.v === 1) save = deepMerge(structuredClone(DEFAULT_SAVE), s);
}
function deepMerge(base, over) {
  for (const k of Object.keys(over || {})) {
    if (over[k] && typeof over[k] === 'object' && !Array.isArray(over[k]) && base[k] && typeof base[k] === 'object') deepMerge(base[k], over[k]);
    else base[k] = over[k];
  }
  return base;
}
let saveTimer = 0;
function persist() { clearTimeout(saveTimer); saveTimer = setTimeout(() => storage.set('fuse.save', save), 150); }

// ---------------------------------------------------------------- dates
const today = () => new Date();
const keyToDate = (k) => new Date(Math.floor(k / 10000), Math.floor(k / 100) % 100 - 1, k % 100);
const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
const daysBetween = (ka, kb) => Math.round((Date.UTC(...ymd(kb)) - Date.UTC(...ymd(ka))) / 86400000);
function ymd(k) { const d = keyToDate(k); return [d.getFullYear(), d.getMonth(), d.getDate()]; }
function isoWeekKey(d = today()) {
  const t = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
  const day = t.getUTCDay() || 7; t.setUTCDate(t.getUTCDate() + 4 - day);
  const y0 = new Date(Date.UTC(t.getUTCFullYear(), 0, 1));
  return t.getUTCFullYear() + '-W' + Math.ceil(((t - y0) / 86400000 + 1) / 7);
}
function msToMidnight() { const n = today(); const m = new Date(n.getFullYear(), n.getMonth(), n.getDate() + 1); return m - n; }
const fmtHMS = (ms) => { const s = Math.max(0, Math.floor(ms / 1000)); return [s / 3600, (s % 3600) / 60, s % 60].map((v) => String(Math.floor(v)).padStart(2, '0')).join(':'); };

// ---------------------------------------------------------------- streak
/** Effective streak shown on home: breaks if last play older than yesterday and no freezes can cover. */
function currentStreak() {
  const st = save.streak;
  if (!st.last) return 0;
  const gap = daysBetween(st.last, dateKey(today()));
  if (gap <= 1) return st.count;
  return gap - 1 <= st.freezes ? st.count : 0;
}
function recordStreak(k) {
  const st = save.streak;
  if (st.last === k) return { changed: false };
  let usedFreeze = 0;
  if (!st.last) st.count = 1;
  else {
    const gap = daysBetween(st.last, k);
    if (gap < 0) return { changed: false }; // archive play: never affects streak
    if (gap === 1) st.count++;
    else if (gap - 1 <= st.freezes) { usedFreeze = gap - 1; st.freezes -= usedFreeze; st.count++; }
    else st.count = 1;
  }
  st.last = k;
  st.best = Math.max(st.best, st.count);
  let earnedFreeze = false;
  if (st.count % 7 === 0 && st.freezes < 2) { st.freezes++; earnedFreeze = true; }
  return { changed: true, usedFreeze, earnedFreeze };
}

// ---------------------------------------------------------------- dom helpers
const $ = (sel, root = document) => root.querySelector(sel);
const app = $('#app');
function h(html) { const t = document.createElement('template'); t.innerHTML = html.trim(); return t.content.firstElementChild; }
let toastTimer = 0;
function toast(msg, ms = 2200) {
  const t = $('#toast'); t.textContent = msg; t.classList.add('on');
  clearTimeout(toastTimer); toastTimer = setTimeout(() => t.classList.remove('on'), ms);
}
function openSheet(el, { onClose } = {}) {
  const sh = $('#sheet'), bk = $('#sheetBack');
  sh.innerHTML = ''; sh.appendChild(el);
  sh.classList.add('on'); bk.classList.add('on');
  bk.onclick = () => { closeSheet(); onClose?.(); };
  sheetOpen = true;
}
let sheetOpen = false;
function closeSheet() { $('#sheet').classList.remove('on'); $('#sheetBack').classList.remove('on'); sheetOpen = false; }
function buzz(kind) { if (save.settings.haptics) haptics[kind]?.(); }

/** Gate a bonus behind a rewarded ad. Outside the native app (dev), it is free. */
async function rewarded(what) {
  if (!isNative) { toast('(dev) reward granted: ' + what); return true; }
  if (!ads.enabled) { toast('Ads unavailable right now'); return false; }
  if (!ads.rewardedAvailable) { toast('Ad still loading, try again in a moment'); ads.preloadRewarded(); return false; }
  const ok = await ads.showRewarded();
  if (!ok) toast('Watch the full ad to get the reward');
  return ok;
}

ads.onBannerSize((px) => document.documentElement.style.setProperty('--banner', px + 'px'));
function bannerFor(screen) {
  // banner only on menus, never on a board, never in a player's first session
  if (screen === 'home' && save.stats.sessions > 1) ads.showBanner(); else ads.hideBanner();
}

// ---------------------------------------------------------------- screens
let current = null;
let view = null;
let tickTimer = 0;
const backStack = [];

function show(name, ...args) {
  if (view) { view.stop(); view = null; }
  clearInterval(tickTimer);
  closeSheet();
  app.innerHTML = '';
  current = name;
  const el = SCREENS[name](...args);
  el.classList.add('screen', 'on');
  app.appendChild(el);
  bannerFor(name);
}

const SCREENS = {
  home() {
    const k = dateKey(today());
    const d = save.daily[k];
    const size = dailySize(today());
    const num = dailyNumber(today());
    const streak = currentStreak();
    const el = h(`<div>
      <div class="scroll">
        <div class="center">
          <div class="logo">FUSE</div>
          <div class="tag">one tap · one chain · same board for everyone</div>
          <div class="chips">
            <span class="chip">🔥 ${streak} day${streak === 1 ? '' : 's'}</span>
            <span class="chip" title="streak freezes">❄️ ${save.streak.freezes}</span>
            <span class="chip">⭐ ${save.stats.perfects}</span>
          </div>
        </div>
        <button class="card hero" id="dailyCard">
          <span class="cta ${d ? 'done' : ''}">${d ? '✓ ' + d.s + '/' + d.b : 'PLAY'}</span>
          <h3>Daily Fuse #${num}</h3>
          <p id="dailySub">${size}×${size}${size === 8 ? ' · Big Weekend board' : ''} · ${d ? 'next board in <b id="cd"></b>' : 'one tap. make it count.'}</p>
        </button>
        <button class="card" id="endlessCard">
          <span class="cta">GO</span>
          <h3>Endless</h3>
          <p>10 taps per round · best ${save.endless.best} · this week ${save.endless.weekKey === isoWeekKey() ? save.endless.week : 0}</p>
        </button>
        <div class="grid4">
          <button class="tile-btn" id="mapBtn"><b>🗓️</b>Fuse Map</button>
          <button class="tile-btn" id="statsBtn"><b>📊</b>Stats</button>
          <button class="tile-btn" id="howBtn"><b>❓</b>How to</button>
          <button class="tile-btn" id="setBtn"><b>⚙️</b>Settings</button>
        </div>
        <p class="sub center" style="margin-top:18px">New board every midnight. Your streak counts plays, not perfect scores.</p>
      </div>
    </div>`);
    $('#dailyCard', el).onclick = () => { sound.unlock(); show('daily', k); };
    $('#endlessCard', el).onclick = () => { sound.unlock(); show('endless'); };
    $('#mapBtn', el).onclick = () => show('map');
    $('#statsBtn', el).onclick = () => openStats();
    $('#howBtn', el).onclick = () => show('tutorial');
    $('#setBtn', el).onclick = () => openSettings();
    if (d) {
      const upd = () => { const c = $('#cd', el); if (c) c.textContent = fmtHMS(msToMidnight()); if (dateKey(today()) !== k) show('home'); };
      upd(); tickTimer = setInterval(upd, 1000);
    }
    return el;
  },

  daily(k, opts = {}) {
    const date = keyToDate(k);
    const isToday = k === dateKey(today());
    const n = dailySize(date);
    const { board, optimal } = generateBoard(n, dailySeed(date));
    const prev = save.daily[k];
    const el = gameShell({
      title: `Daily Fuse #${dailyNumber(date)}`,
      sub: isToday ? `${n}×${n} · one tap` : `${date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })} · archive`,
    });
    const hud = $('.hud', el);
    hud.innerHTML = `<div><b id="hScore">–</b><span>burnt</span></div><div><b>${board.cells.filter((c) => c.t !== TILE.WALL).length}</b><span>tiles</span></div><div><b id="hBest">?</b><span>best possible</span></div>`;
    const hint = $('.hint', el);
    const actions = $('.actions', el);
    view = makeView(el);
    view.setBoard(board, prev ? maskToBurnt(prev.m) : null);
    view.start();

    let selected = null, committed = !!prev;
    const lightBtn = h(`<button class="btn primary" disabled>LIGHT IT 🔥</button>`);
    if (!committed) {
      hint.textContent = 'Study the board. Tap a tile, then light it. You get one tap.';
      actions.appendChild(lightBtn);
    } else {
      $('#hScore', el).textContent = prev.s; $('#hBest', el).textContent = prev.b;
      hint.textContent = 'Already played. Come back at midnight for a new board.';
      setTimeout(() => showDailyResult(), 250);
    }
    bindTap(view, (cell) => {
      if (committed) return;
      if (board.cells[idx(n, cell.r, cell.c)].t === TILE.WALL) { buzz('error'); return; }
      if (selected && selected.r === cell.r && selected.c === cell.c) { commit(); return; }
      selected = cell; view.selected = cell; lightBtn.disabled = false; sound.tick(); buzz('light');
      hint.textContent = 'Tap LIGHT IT (or tap the tile again) to commit.';
    });
    lightBtn.onclick = () => selected && commit();

    async function commit() {
      committed = true; lightBtn.remove(); view.selected = null;
      hint.textContent = '';
      const res = simulate(board, selected.r, selected.c);
      await playChain(view, res, $('#hScore', el));
      const perfect = res.count >= optimal.count;
      $('#hBest', el).textContent = optimal.count;
      const rec = { s: res.count, b: optimal.count, m: burntToMask(board, res.burnt), t: [selected.r, selected.c] };
      if (!save.daily[k]) {
        save.daily[k] = rec;
        save.stats.dailies++;
        save.stats.gapSum += optimal.count - res.count;
        if (perfect) save.stats.perfects++;
        save.stats.biggest = Math.max(save.stats.biggest, res.count);
        const sr = isToday ? recordStreak(k) : { changed: false };
        persist();
        if (sr.usedFreeze) toast(`❄️ Streak freeze used`);
        if (sr.earnedFreeze) setTimeout(() => toast('❄️ 7-day streak! Earned a streak freeze'), 1200);
      }
      sound.fanfare(perfect); buzz(perfect ? 'success' : 'medium');
      showDailyResult(true);
    }

    function showDailyResult(fresh = false) {
      const r = save.daily[k];
      const gap = r.b - r.s;
      const pct = Math.round(percentileOnBoard(optimal.scores, r.s) * 100);
      const cls = gap === 0 ? 'perfect' : gap <= 5 ? 'close' : 'far';
      const label = gap === 0 ? 'PERFECT' : gap <= 5 ? `${gap} off the best` : `${gap} off the best`;
      const sheet = h(`<div class="center">
        <div class="sub">Daily Fuse #${dailyNumber(date)}</div>
        <div class="big">${r.s}<small> / ${r.b}</small></div>
        <div style="margin:8px 0"><span class="badge ${cls}">${label}</span></div>
        <div class="sub">Better than ${pct}% of possible taps on this board</div>
        ${isToday ? `<div class="sub" style="margin-top:6px">🔥 streak ${currentStreak()} · next board in <b id="cd2">${fmtHMS(msToMidnight())}</b></div>` : ''}
        <div class="actions" style="margin-top:16px">
          <button class="btn primary" id="shareBtn">SHARE</button>
          <button class="btn" id="bestBtn">${gap === 0 ? 'REPLAY' : 'SHOW BEST'}</button>
        </div>
        <div class="actions" style="margin-top:10px">
          <button class="btn small" id="postBtn">📸 Post the puzzle</button>
          <button class="btn small ad" id="practBtn">Practice tap</button>
          <button class="btn small" id="homeBtn">Home</button>
        </div>
      </div>`);
      $('#shareBtn', sheet).onclick = () => shareDaily(k, board, r);
      $('#bestBtn', sheet).onclick = async () => {
        closeSheet();
        view.setBoard(board); view.pulse = { r: optimal.r, c: optimal.c };
        hint.textContent = gap === 0 ? 'Your perfect chain' : `Best start: row ${optimal.r + 1}, col ${optimal.c + 1}`;
        await sleep(700); view.pulse = null;
        await playChain(view, simulate(board, optimal.r, optimal.c), $('#hScore', el), 1.2);
        await sleep(600);
        view.setBoard(board, maskToBurnt(r.m));
        $('#hScore', el).textContent = r.s;
        showDailyResult();
      };
      $('#postBtn', sheet).onclick = () => postPuzzle(k, board);
      $('#practBtn', sheet).onclick = async () => {
        if (!(await rewarded('practice tap'))) return;
        closeSheet(); practice();
      };
      $('#homeBtn', sheet).onclick = () => show('home');
      openSheet(sheet);
      if (isToday) { clearInterval(tickTimer); tickTimer = setInterval(() => { const c = $('#cd2'); if (c) c.textContent = fmtHMS(msToMidnight()); }, 1000); }
      if (fresh && r.s === r.b) burstConfetti(view);
    }

    function practice() {
      // a second, clearly-labelled tap that never changes the shared result
      view.setBoard(board);
      $('#hScore', el).textContent = '–';
      hint.textContent = 'PRACTICE: tap a tile twice. Your real score stays the same.';
      let sel = null, done = false;
      bindTap(view, async (cell) => {
        if (done || board.cells[idx(n, cell.r, cell.c)].t === TILE.WALL) return;
        if (!sel || sel.r !== cell.r || sel.c !== cell.c) { sel = cell; view.selected = cell; sound.tick(); return; }
        done = true; view.selected = null;
        const res = simulate(board, cell.r, cell.c);
        await playChain(view, res, $('#hScore', el));
        hint.textContent = `Practice: ${res.count} / ${optimal.count}`;
        await sleep(900);
        view.setBoard(board, maskToBurnt(save.daily[k].m));
        $('#hScore', el).textContent = save.daily[k].s;
        showDailyResult();
      });
    }
    return el;
  },

  endless() {
    const el = gameShell({ title: 'Endless', sub: `${ENDLESS_N}×${ENDLESS_N} · chains of ${BIG_CHAIN}+ refund the tap` });
    const hud = $('.hud', el);
    hud.innerHTML = `<div><b id="eTaps">${ENDLESS_TAPS}</b><span>taps</span></div><div><b id="eScore">0</b><span>score</span></div><div><b>${save.endless.best}</b><span>best</span></div>`;
    const hint = $('.hint', el);
    const actions = $('.actions', el);
    const seed = (Date.now() ^ (save.endless.rounds * 7919)) >>> 0;
    const rng = mulberry32(seed);
    let board = generateBoard(ENDLESS_N, seed).board;
    let taps = ENDLESS_TAPS, score = 0, busy = false, revived = false, previewLeft = 1, previewOn = false, biggest = 0, sel = null;
    view = makeView(el);
    view.setBoard(board); view.start();
    hint.textContent = 'Tap a tile to select, tap again to light it.';
    const prevBtn = h(`<button class="btn small ad">Preview spark</button>`);
    actions.appendChild(prevBtn);
    prevBtn.onclick = async () => {
      if (previewLeft <= 0) return;
      if (!(await rewarded('preview spark'))) return;
      previewLeft--; previewOn = true; prevBtn.disabled = true; prevBtn.textContent = 'Preview ON (this tap)';
      if (sel) view.overlay = { ...sel, text: String(simulate(board, sel.r, sel.c).count) };
    };
    const upd = () => { $('#eTaps', el).textContent = taps; $('#eScore', el).textContent = score; };

    bindTap(view, async (cell) => {
      if (busy) return;
      const i = idx(board.n, cell.r, cell.c);
      if (board.cells[i].t === TILE.WALL) { buzz('error'); sound.thud(); return; }
      if (!sel || sel.r !== cell.r || sel.c !== cell.c) {
        sel = cell; view.selected = cell; sound.tick(); buzz('light');
        view.overlay = previewOn ? { ...cell, text: String(simulate(board, cell.r, cell.c).count) } : null;
        return;
      }
      busy = true; view.selected = null; view.overlay = null; sel = null;
      if (previewOn) { previewOn = false; prevBtn.textContent = previewLeft > 0 ? 'Preview spark' : 'Preview used'; }
      taps--;
      const res = simulate(board, cell.r, cell.c);
      const base = score;
      await playChain(view, res, null, 1, (k) => { $('#eScore', el).textContent = base + k + 1; });
      const pts = res.count + Math.floor(res.count * res.count / 10);
      score += pts; biggest = Math.max(biggest, res.count);
      if (res.count >= BIG_CHAIN) { taps++; toast(`🔥 ${res.count}-chain! +${pts} · tap refunded`); sound.fanfare(false); buzz('success'); }
      else if (res.count >= 6) toast(`+${pts}`, 900);
      upd();
      const col = collapse(board, res.burnt, rng);
      board = col.board;
      await view.dropIn(board, col.moves);
      busy = false;
      if (taps <= 0) roundOver();
    });

    function roundOver() {
      busy = true;
      const isBest = score > save.endless.best;
      save.endless.rounds++;
      if (isBest) save.endless.best = score;
      const wk = isoWeekKey();
      if (save.endless.weekKey !== wk) { save.endless.weekKey = wk; save.endless.week = 0; }
      save.endless.week = Math.max(save.endless.week, score);
      save.stats.biggest = Math.max(save.stats.biggest, biggest);
      persist();
      if (isBest) { sound.fanfare(true); buzz('success'); burstConfetti(view); }
      const sheet = h(`<div class="center">
        <div class="sub">Round over</div>
        <div class="big">${score}</div>
        <div style="margin:8px 0">${isBest ? '<span class="badge perfect">NEW BEST!</span>' : `<span class="sub">best ${save.endless.best}</span>`}</div>
        <div class="sub">Biggest chain ${biggest} · week best ${save.endless.week}</div>
        <div class="actions" style="margin-top:16px">
          ${!revived ? '<button class="btn primary ad" id="revBtn">+3 TAPS</button>' : ''}
          <button class="btn ${revived ? 'primary' : ''}" id="againBtn">PLAY AGAIN</button>
        </div>
        <div class="actions" style="margin-top:10px">
          <button class="btn small" id="shareE">Share score</button>
          <button class="btn small" id="homeE">Home</button>
        </div>
      </div>`);
      const rb = $('#revBtn', sheet);
      if (rb) rb.onclick = async () => {
        if (!(await rewarded('+3 taps'))) return;
        revived = true; taps += 3; busy = false; upd(); closeSheet(); hint.textContent = '+3 taps. Make them count!';
      };
      $('#againBtn', sheet).onclick = async () => { await maybeInterstitial(); show('endless'); };
      $('#homeE', sheet).onclick = async () => { await maybeInterstitial(); show('home'); };
      $('#shareE', sheet).onclick = () => share({
        title: 'FUSE',
        text: `FUSE Endless 🔥 ${score} points · biggest chain ${biggest}${isBest ? ' · new personal best!' : ''}\nCan you beat it? ${STORE_URL}`,
      }).then((r) => r === 'copied' && toast('Copied to clipboard'));
      openSheet(sheet);
    }
    return el;
  },

  tutorial() {
    const el = gameShell({ title: 'How to play', sub: 'tap the glowing tile twice' });
    // hand-made 5x5 board: a clear snake path from (4,0) with a splitter and a bomb
    const A = (d) => ({ t: TILE.ARROW, d }), S = { t: TILE.SPLIT, d: 0 }, B = (d) => ({ t: TILE.BOMB, d }), W = { t: TILE.WALL, d: 0 };
    const cells = [
      A(1), A(1), A(1), A(2), W,
      A(0), W, A(3), A(2), A(3),
      A(0), A(3), S, A(3), A(0),
      A(0), A(2), A(1), B(0), A(0),
      A(0), A(3), A(0), A(1), A(0),
    ];
    const board = makeBoard(5, cells);
    const best = solve(board);
    $('.hud', el).innerHTML = `<div><b id="tScore">0</b><span>burnt</span></div><div><b>${best.count}</b><span>best possible</span></div>`;
    const hint = $('.hint', el);
    const actions = $('.actions', el);
    view = makeView(el);
    view.setBoard(board); view.start();
    view.pulse = { r: best.r, c: best.c };
    const legend = h(`<div class="legend"></div>`);
    const items = [
      [A(0), 'Arrow: the spark flies this way, over burnt tiles, to the next tile'],
      [S, 'Splitter: the spark splits left and right of the way it came in'],
      [B(1), 'Bomb: burns everything around it, then fires its own arrow'],
      [W, 'Wall: stops the spark dead'],
    ];
    for (const [cell, text] of items) {
      const c = document.createElement('canvas'); c.width = 80; c.height = 80;
      drawTile(c.getContext('2d'), cell, 0, 0, 80);
      legend.appendChild(c); legend.appendChild(h(`<div>${text}</div>`));
    }
    hint.textContent = 'You get ONE tap. Pick the tile that burns the most.';
    let sel = null, done = false;
    bindTap(view, async (cell) => {
      if (done || board.cells[idx(5, cell.r, cell.c)].t === TILE.WALL) return;
      if (!sel || sel.r !== cell.r || sel.c !== cell.c) { sel = cell; view.selected = cell; sound.tick(); hint.textContent = 'Tap it again to light it.'; return; }
      done = true; view.selected = null; view.pulse = null;
      const res = simulate(board, cell.r, cell.c);
      await playChain(view, res, $('#tScore', el));
      hint.textContent = res.count === best.count ? 'PERFECT! That is the whole game. ' : `${res.count} of ${best.count}. The glowing tile was best.`;
      actions.innerHTML = '';
      const again = h(`<button class="btn">Try again</button>`); again.onclick = () => show('tutorial');
      const go = h(`<button class="btn primary">${save.onboarded ? 'Done' : "Play today's board"}</button>`);
      go.onclick = () => { const first = !save.onboarded; save.onboarded = true; persist(); first ? show('daily', dateKey(today())) : show('home'); };
      actions.append(again, go);
    });
    const info = h(`<button class="btn small">Tile guide</button>`);
    info.onclick = () => { const s = h(`<div><h2>Tiles</h2></div>`); s.appendChild(legend); const b = h(`<button class="btn primary" style="width:100%">Got it</button>`); b.onclick = closeSheet; s.appendChild(b); openSheet(s); };
    actions.appendChild(info);
    return el;
  },

  map(monthOffset = 0) {
    const base = today();
    const first = new Date(base.getFullYear(), base.getMonth() + monthOffset, 1);
    const ym = first.getFullYear() * 100 + first.getMonth() + 1;
    const days = new Date(first.getFullYear(), first.getMonth() + 1, 0).getDate();
    const tk = dateKey(base);
    const el = h(`<div>
      <div class="topbar"><button class="iconbtn" id="back">‹</button><div class="grow"><div class="title">Fuse Map</div><div class="sub">every day you play adds its burn shape</div></div></div>
      <div class="row" style="margin-top:12px"><button class="iconbtn" id="prevM">‹</button><div class="grow center title">${first.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })}</div><button class="iconbtn" id="nextM" ${monthOffset >= 0 ? 'disabled style="opacity:.3"' : ''}>›</button></div>
      <div class="cal" id="cal"></div>
      <p class="sub center" id="mapSum"></p>
      <p class="sub center">Missed a day? Tap it to play that board. Yesterday is free. Older days: watch an ad (2 per month).</p>
    </div>`);
    $('#back', el).onclick = () => show('home');
    $('#prevM', el).onclick = () => show('map', monthOffset - 1);
    $('#nextM', el).onclick = () => monthOffset < 0 && show('map', monthOffset + 1);
    const cal = $('#cal', el);
    'MTWTFSS'.split('').forEach((d) => cal.appendChild(h(`<div class="dow">${d}</div>`)));
    const lead = (first.getDay() + 6) % 7;
    for (let i = 0; i < lead; i++) cal.appendChild(h('<div></div>'));
    let played = 0, perfect = 0;
    for (let d = 1; d <= days; d++) {
      const k = ym * 100 + d;
      const rec = save.daily[k];
      const cell = h(`<button class="day"><span class="n">${d}</span></button>`);
      const beforeEpoch = dailyNumber(keyToDate(k)) < 1;
      if (rec) {
        const c = document.createElement('canvas'); drawSilhouette(c, rec.m); cell.appendChild(c);
        const gap = rec.b - rec.s; cell.classList.add(gap === 0 ? 'g' : gap <= 5 ? 'y' : 'r');
        played++; if (gap === 0) perfect++;
        cell.onclick = () => show('daily', k);
      } else if (k === tk) {
        cell.classList.add('today'); cell.appendChild(h('<span>today</span>'));
        cell.onclick = () => show('daily', k);
      } else if (k > tk || beforeEpoch) {
        cell.classList.add('future');
      } else {
        cell.classList.add('missed'); cell.appendChild(h('<span>✕</span>'));
        cell.onclick = () => playArchive(k);
      }
      cal.appendChild(cell);
    }
    $('#mapSum', el).textContent = `${played} played · ${perfect} perfect${played === days ? ' · MONTH COMPLETE 🏆' : ''}`;
    return el;
  },
};

async function playArchive(k) {
  const age = daysBetween(k, dateKey(today()));
  if (age <= 1) return show('daily', k);
  const mk = String(Math.floor(k / 100));
  const used = save.repairs[mk] || 0;
  if (used >= 2) { toast('You already repaired 2 days this month'); return; }
  if (!(await rewarded('archive board'))) return;
  save.repairs[mk] = used + 1; persist();
  show('daily', k);
}

// ---------------------------------------------------------------- shared game bits
function gameShell({ title, sub }) {
  const el = h(`<div>
    <div class="topbar"><button class="iconbtn" id="back">‹</button><div class="grow"><div class="title">${title}</div><div class="sub">${sub}</div></div></div>
    <div class="hud"></div>
    <div class="board-wrap"><canvas class="board"></canvas></div>
    <div class="hint"></div>
    <div class="actions"></div>
  </div>`);
  $('#back', el).onclick = () => show('home');
  return el;
}

function makeView(el) {
  const v = new BoardView($('canvas', el));
  v.reduced = save.settings.reduced;
  requestAnimationFrame(() => v.layout());
  return v;
}

function bindTap(v, fn) {
  v.cv.onpointerdown = (e) => {
    e.preventDefault();
    sound.unlock();
    const cell = v.hit(e.clientX, e.clientY);
    if (cell && !v.anim) fn(cell);
  };
}

/** Animate a chain with sound + haptics. counterEl gets live burn counts. */
function playChain(v, res, counterEl, speed = 1, onCount) {
  let lastBuzz = 0;
  v.hooks.onBurn = (k, e) => {
    sound.pop(k, e.tile === TILE.BOMB ? 2 : e.tile === TILE.SPLIT ? 1 : 0);
    const now = performance.now();
    if (now - lastBuzz > 45) { buzz('light'); lastBuzz = now; }
    if (counterEl) counterEl.textContent = k + 1;
    onCount?.(k);
  };
  v.hooks.onBomb = () => { sound.boom(); buzz('heavy'); };
  return v.play(res.events, { speed: speed * (res.count > 30 ? 1.4 : 1) });
}

function burstConfetti(v) {
  if (!v || !v.board) return;
  const n = v.board.n;
  const cols = ['#ffe08a', '#ff8a1f', '#3ddc84', '#56B4E9', '#CC79A7'];
  for (let k = 0; k < 6; k++) setTimeout(() => v.burst(Math.random() * n - 0.5, Math.random() * n - 0.5, cols[k % cols.length], 24), k * 90);
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
function burntToMask(board, burnt) { return board.cells.map((c, i) => (burnt[i] ? '1' : c.t === TILE.WALL ? '2' : '0')).join(''); }
function maskToBurnt(mask) { return [...mask].map((ch) => ch === '1'); }

let sessionInterstitials = 0;
async function maybeInterstitial() {
  if (save.stats.sessions < 3 || sessionInterstitials >= 5) return;
  if (save.endless.rounds % 3 !== 0) return;
  const shown = await ads.maybeShowInterstitial({ gamesPlayed: save.endless.rounds, minGames: 2 });
  if (shown) sessionInterstitials++;
}

// ---------------------------------------------------------------- sharing
function shareText(k, r) {
  const gap = r.b - r.s;
  const date = keyToDate(k);
  const n = dailySize(date);
  const { board } = generateBoard(n, dailySeed(date));
  const sil = emojiSilhouette(board, maskToBurnt(r.m));
  const verdict = gap === 0 ? '⭐ PERFECT' : `${gap} off best`;
  const st = currentStreak();
  return `FUSE #${dailyNumber(date)} 🔥 ${r.s}/${r.b} · ${verdict}${st > 1 ? ` · ${st}-day streak` : ''}\n${sil}\n${STORE_URL}`;
}

async function shareDaily(k, board, r) {
  // Text share is the primary format (pastes everywhere); image card attached when possible.
  const text = shareText(k, r);
  const png = await cardImage(board, r, k);
  const res = await shareWithImage(text, png, `fuse-${dailyNumber(keyToDate(k))}.png`);
  if (res === 'copied') toast('Result copied to clipboard');
}

async function postPuzzle(k, board) {
  const date = keyToDate(k);
  const cv = document.createElement('canvas');
  const W = 1080, H = 1350; cv.width = W; cv.height = H;
  const ctx = cv.getContext('2d');
  ctx.fillStyle = '#0e0d13'; ctx.fillRect(0, 0, W, H);
  ctx.fillStyle = '#ff8a1f'; ctx.font = '900 92px system-ui, sans-serif'; ctx.textAlign = 'center';
  ctx.fillText('Which tile would you tap?', W / 2, 140);
  ctx.fillStyle = '#a39fb5'; ctx.font = '600 44px system-ui, sans-serif';
  ctx.fillText(`FUSE #${dailyNumber(date)} · one tap · burn the most tiles`, W / 2, 210);
  const img = renderBoardImage(board, null, 900);
  ctx.drawImage(img, 90, 270);
  ctx.fillStyle = '#a39fb5'; ctx.font = '600 40px system-ui, sans-serif';
  ctx.fillText('Comment row + column 👇  ·  FUSE on Google Play', W / 2, 1250);
  const png = cv.toDataURL('image/png');
  const res = await shareWithImage(`Which tile would you tap? FUSE #${dailyNumber(date)} 🔥 ${STORE_URL}`, png, `fuse-puzzle-${dailyNumber(date)}.png`);
  if (res === 'copied') toast('Caption copied');
}

async function cardImage(board, r, k) {
  const cv = document.createElement('canvas');
  const W = 1080, H = 1350; cv.width = W; cv.height = H;
  const ctx = cv.getContext('2d');
  const g = ctx.createLinearGradient(0, 0, 0, H); g.addColorStop(0, '#1d1428'); g.addColorStop(1, '#0e0d13');
  ctx.fillStyle = g; ctx.fillRect(0, 0, W, H);
  ctx.textAlign = 'center';
  ctx.fillStyle = '#ff8a1f'; ctx.font = '900 120px system-ui, sans-serif'; ctx.fillText('FUSE', W / 2, 150);
  ctx.fillStyle = '#f4f1ea'; ctx.font = '800 64px system-ui, sans-serif';
  ctx.fillText(`#${dailyNumber(keyToDate(k))}  ·  ${r.s} / ${r.b}${r.s === r.b ? '  ⭐' : ''}`, W / 2, 245);
  const img = renderBoardImage(board, maskToBurnt(r.m), 860, { glow: true });
  ctx.drawImage(img, 110, 300);
  ctx.fillStyle = '#a39fb5'; ctx.font = '600 40px system-ui, sans-serif';
  ctx.fillText('one tap · one chain · same board for everyone', W / 2, 1240);
  return cv.toDataURL('image/png');
}

async function shareWithImage(text, dataUrl, name) {
  if (isNative) {
    try {
      const b64 = dataUrl.split(',')[1];
      const f = await Filesystem.writeFile({ path: name, data: b64, directory: Directory.Cache });
      await Share.share({ text, files: [f.uri], dialogTitle: 'Share' });
      return true;
    } catch (e) {
      return share({ title: 'FUSE', text });
    }
  }
  try {
    const blob = await (await fetch(dataUrl)).blob();
    const file = new File([blob], name, { type: 'image/png' });
    if (navigator.canShare?.({ files: [file] })) { await navigator.share({ text, files: [file] }); return true; }
  } catch {}
  return share({ title: 'FUSE', text });
}

// ---------------------------------------------------------------- sheets: stats, settings
function openStats() {
  const s = save.stats;
  const avgGap = s.dailies ? (s.gapSum / s.dailies).toFixed(1) : '–';
  const el = h(`<div>
    <h2>Stats</h2>
    <div class="stats-grid">
      <div class="stat"><b>${s.dailies}</b><span>dailies played</span></div>
      <div class="stat"><b>${s.perfects}</b><span>perfect dailies</span></div>
      <div class="stat"><b>${currentStreak()}</b><span>current streak</span></div>
      <div class="stat"><b>${save.streak.best}</b><span>best streak</span></div>
      <div class="stat"><b>${avgGap}</b><span>avg tiles off best</span></div>
      <div class="stat"><b>${s.gapSum}</b><span>lifetime tiles off best</span></div>
      <div class="stat"><b>${save.endless.best}</b><span>endless best</span></div>
      <div class="stat"><b>${s.biggest}</b><span>biggest chain</span></div>
    </div>
    <button class="btn primary" style="width:100%" id="ok">Close</button>
  </div>`);
  $('#ok', el).onclick = closeSheet;
  openSheet(el);
}

function openSettings() {
  const st = save.settings;
  const el = h(`<div>
    <h2>Settings</h2>
    <div class="setting"><span>Sound</span><button class="switch ${st.sound ? 'on' : ''}" data-k="sound"></button></div>
    <div class="setting"><span>Haptics</span><button class="switch ${st.haptics ? 'on' : ''}" data-k="haptics"></button></div>
    <div class="setting"><span>Reduced motion</span><button class="switch ${st.reduced ? 'on' : ''}" data-k="reduced"></button></div>
    <div class="setting"><span>Backup progress</span><button class="btn small" id="exp">Export</button></div>
    <div class="setting"><span>Restore progress</span><button class="btn small" id="imp">Import</button></div>
    ${isNative ? '<div class="setting"><span>Ad privacy choices</span><button class="btn small" id="privacy">Open</button></div>' : ''}
    <p class="sub">FUSE stores everything on this device. No account, no tracking beyond what the ad network needs. <a href="https://mohit-aggarwal479.github.io/test1/privacy.html">Privacy policy</a></p>
    <button class="btn primary" style="width:100%" id="ok">Done</button>
  </div>`);
  el.querySelectorAll('.switch').forEach((b) => b.onclick = () => {
    const k = b.dataset.k; st[k] = !st[k]; b.classList.toggle('on', st[k]);
    sound.setEnabled(st.sound); persist();
  });
  $('#exp', el).onclick = async () => {
    const code = btoa(unescape(encodeURIComponent(JSON.stringify(save))));
    const r = await share({ title: 'FUSE backup', text: 'FUSE-BACKUP:' + code });
    if (r === 'copied') toast('Backup code copied');
  };
  $('#imp', el).onclick = async () => {
    const raw = prompt('Paste your FUSE backup code');
    if (!raw) return;
    try {
      const data = JSON.parse(decodeURIComponent(escape(atob(raw.trim().replace(/^FUSE-BACKUP:/, '')))));
      if (data.v !== 1) throw 0;
      save = deepMerge(structuredClone(DEFAULT_SAVE), data); persist(); toast('Progress restored'); show('home');
    } catch { toast('That code did not work'); }
  };
  const pv = $('#privacy', el);
  if (pv) pv.onclick = async () => { try { const { AdMob } = await import('@capacitor-community/admob'); await AdMob.showPrivacyOptionsForm(); } catch { toast('Not required in your region'); } };
  $('#ok', el).onclick = closeSheet;
  openSheet(el);
}

// ---------------------------------------------------------------- boot
window.addEventListener('resize', () => view?.layout());
App.addListener('backButton', () => {
  if (sheetOpen) { closeSheet(); return; }
  if (current && current !== 'home') { show('home'); return; }
  App.exitApp();
}).catch?.(() => {});
document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible' && current === 'home') show('home');
});

(async function boot() {
  await load();
  save.stats.sessions++;
  persist();
  sound.setEnabled(save.settings.sound);
  setupSystemUI('#0e0d13');
  ads.init();
  if (!save.onboarded) show('tutorial'); else show('home');
})();

// dev hook for automated checks
window.__fuse = { get save() { return save; }, show, generateBoard, simulate };
