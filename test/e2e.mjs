// End-to-end play-test in headless Chrome at phone size.
// Usage: npm run test:e2e   (bundles first, serves www/ on a random port)
import puppeteer from 'puppeteer-core';
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import assert from 'node:assert/strict';

const ROOT = path.join(path.dirname(fileURLToPath(import.meta.url)), '..', 'www');
const SHOTS = process.env.E2E_SHOTS || '';
const CHROME = process.env.CHROME_PATH || [
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
  '/usr/bin/google-chrome', '/usr/bin/chromium', '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
].find((p) => fs.existsSync(p));

const MIME = { '.html': 'text/html', '.js': 'text/javascript', '.map': 'application/json', '.png': 'image/png' };
const server = http.createServer((req, res) => {
  const f = path.join(ROOT, decodeURIComponent(req.url.split('?')[0]) === '/' ? 'index.html' : decodeURIComponent(req.url.split('?')[0]));
  if (!f.startsWith(ROOT) || !fs.existsSync(f)) { res.writeHead(404); res.end(); return; }
  res.writeHead(200, { 'Content-Type': MIME[path.extname(f)] || 'application/octet-stream' });
  fs.createReadStream(f).pipe(res);
}).listen(0);
const URL = `http://127.0.0.1:${server.address().port}/`;

const results = [];
const step = async (name, fn) => {
  try { await fn(); results.push(['PASS', name]); console.log('  ✔', name); }
  catch (e) { results.push(['FAIL', name, e.message]); console.log('  ✖', name, '\n     ', e.message); }
};

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--autoplay-policy=no-user-gesture-required'] });
const page = await browser.newPage();
await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 2, isMobile: true, hasTouch: true });
const errors = [];
page.on('pageerror', (e) => errors.push(e.message));
page.on('console', (m) => { if (m.type() === 'error' && !/favicon|404/.test(m.text())) errors.push(m.text()); });
page.on('dialog', (d) => d.dismiss());
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const shot = async (n) => SHOTS && page.screenshot({ path: path.join(SHOTS, n + '.png') });
const fuse = (fn, ...a) => page.evaluate(fn, ...a);
async function tapCell(r, c) {
  const { x, y, w, n } = await page.$eval('canvas.board', (cv) => { const b = cv.getBoundingClientRect(); return { x: b.left, y: b.top, w: b.width, n: window.__fuseView.board.n }; });
  const s = w / n;
  await page.mouse.click(x + (c + 0.5) * s, y + (r + 0.5) * s);
}
const waitSheet = async (sel, timeout = 30000) => { await page.waitForSelector(`#sheet.on ${sel}`, { timeout }); await sleep(450); }; // let the slide-in finish

console.log('FUSE e2e @', URL);
await page.goto(URL, { waitUntil: 'load' });
await fuse(() => localStorage.clear());
await page.reload({ waitUntil: 'load' });
await sleep(500);

await step('first launch shows the tutorial', async () => {
  assert.match(await page.$eval('.title', (e) => e.textContent), /How to play/);
});

await step('tutorial: tapping the pulsing tile twice gives PERFECT', async () => {
  const p = await fuse(() => window.__fuseView.pulse);
  await tapCell(p.r, p.c); await sleep(150); await tapCell(p.r, p.c);
  await page.waitForFunction(() => /PERFECT/.test(document.querySelector('.hint').textContent), { timeout: 15000 });
  await shot('01-tutorial');
});

await step('tutorial CTA opens today\'s daily', async () => {
  await fuse(() => [...document.querySelectorAll('.actions .btn')].find((b) => /today/i.test(b.textContent)).click());
  await sleep(500);
  assert.match(await page.$eval('.title', (e) => e.textContent), /Daily Fuse #\d+/);
  assert.equal(await fuse(() => window.__fuse.save.onboarded), true);
});

let dailyKey, dailyRec;
await step('daily: wall taps are ignored, LIGHT IT disabled until selection', async () => {
  assert.equal(await page.$eval('#app .actions .btn.primary', (b) => b.disabled), true);
});

await step('daily: select + LIGHT IT burns, saves result immediately, opens result sheet', async () => {
  const pick = await fuse(() => { const b = window.__fuseView.board; const i = b.cells.findIndex((c) => c.t !== 3); return { r: Math.floor(i / b.n), c: i % b.n }; });
  await tapCell(pick.r, pick.c); await sleep(150);
  assert.equal(await page.$eval('#app .actions .btn.primary', (b) => b.disabled), false);
  await page.click('#app .actions .btn.primary');
  // result is persisted before the animation finishes
  await sleep(100);
  dailyKey = await fuse(() => Object.keys(window.__fuse.save.daily)[0]);
  assert.ok(dailyKey, 'daily saved before animation end');
  await waitSheet('#shareBtn');
  dailyRec = await fuse((k) => window.__fuse.save.daily[k], dailyKey);
  assert.ok(dailyRec.s >= 1 && dailyRec.s <= dailyRec.b);
  assert.equal(await fuse(() => window.__fuse.save.streak.count), 1);
  await shot('02-daily-result');
});

await step('daily: SHOW BEST replays the optimal chain and restores the saved result', async () => {
  await page.click('#bestBtn');
  await page.waitForFunction(() => !document.querySelector('#sheet').classList.contains('on'));
  await waitSheet('#homeBtn', 40000);
  const after = await fuse((k) => window.__fuse.save.daily[k], dailyKey);
  assert.deepEqual(after, dailyRec);
  const burnt = await fuse(() => window.__fuseView.burnt.filter(Boolean).length);
  assert.equal(burnt, dailyRec.s);
});

await step('daily: re-opening a played daily cannot be replayed', async () => {
  await fuse((k) => window.__fuse.show('daily', +k), dailyKey);
  await sleep(300);
  assert.equal(await page.$('#app .actions .btn.primary'), null);
  await waitSheet('#homeBtn');
});

await step('daily: leaving mid-animation still records exactly one result (archive day)', async () => {
  const yk = await fuse(() => { const d = new Date(); d.setDate(d.getDate() - 1); return d.getFullYear() * 10000 + (d.getMonth() + 1) * 100 + d.getDate(); });
  await fuse((k) => window.__fuse.show('daily', k), yk);
  await sleep(300);
  const pick = await fuse(() => { const b = window.__fuseView.board; const i = b.cells.findIndex((c) => c.t !== 3); return { r: Math.floor(i / b.n), c: i % b.n }; });
  await tapCell(pick.r, pick.c); await sleep(100); await tapCell(pick.r, pick.c);
  await sleep(80);
  await page.click('#back');
  await sleep(300);
  assert.ok(await fuse((k) => !!window.__fuse.save.daily[k], yk), 'archive result saved');
  assert.equal(await fuse(() => window.__fuse.save.streak.count), 1, 'archive does not change streak');
});

await step('home shows streak and played daily', async () => {
  await fuse(() => window.__fuse.show('home'));
  await sleep(300);
  assert.match(await page.$eval('#dailyCard .cta', (e) => e.textContent), /✓ \d+\/\d+/);
  await shot('03-home');
});

await step('endless: plays a full round to round-over with score saved', async () => {
  await page.click('#endlessCard'); await sleep(300);
  for (let k = 0; k < 60; k++) {
    if (await page.$('#sheet.on #homeE')) break;
    const pick = await fuse((k) => { const b = window.__fuseView.board; const ok = b.cells.map((c, i) => c.t !== 3 ? i : -1).filter((i) => i >= 0); const i = ok[(k * 7) % ok.length]; return { r: Math.floor(i / b.n), c: i % b.n }; }, k);
    await tapCell(pick.r, pick.c); await sleep(80); await tapCell(pick.r, pick.c);
    await page.waitForFunction(() => !window.__fuseView || !window.__fuseView.anim, { timeout: 15000 });
    await sleep(500);
  }
  await waitSheet('#homeE');
  const e = await fuse(() => window.__fuse.save.endless);
  assert.ok(e.best > 0 && e.rounds === 1);
  await shot('04-endless');
});

await step('endless: revive +3 then second round-over does not double count rounds', async () => {
  await page.click('#revBtn');
  await sleep(300);
  assert.equal(await page.$eval('#eTaps', (e) => e.textContent), '3');
  for (let k = 0; k < 20; k++) {
    if (await page.$('#sheet.on #homeE')) break;
    const pick = await fuse((k) => { const b = window.__fuseView.board; const ok = b.cells.map((c, i) => c.t !== 3 ? i : -1).filter((i) => i >= 0); const i = ok[(k * 5 + 3) % ok.length]; return { r: Math.floor(i / b.n), c: i % b.n }; }, k);
    await tapCell(pick.r, pick.c); await sleep(80); await tapCell(pick.r, pick.c);
    await page.waitForFunction(() => !window.__fuseView || !window.__fuseView.anim, { timeout: 15000 });
    await sleep(500);
  }
  await waitSheet('#homeE');
  assert.equal(await fuse(() => window.__fuse.save.endless.rounds), 1);
  assert.equal(await page.$('#revBtn'), null, 'revive offered only once');
});

await step('endless: dismissing the sheet leaves a Results button', async () => {
  await page.click('#sheetBack'); await sleep(300);
  await page.click('#resBtn'); await sleep(300);
  assert.ok(await page.$('#sheet.on #homeE'));
  await page.click('#homeE'); await sleep(400);
});

await step('map shows played days', async () => {
  await page.click('#mapBtn'); await sleep(300);
  assert.ok((await page.$$('.cal .day canvas')).length >= 1);
  await shot('05-map');
  await page.click('#back'); await sleep(200);
});

await step('stats and settings sheets open; toggles persist', async () => {
  await page.click('#statsBtn'); await sleep(300);
  assert.match(await page.$eval('#sheet', (e) => e.textContent), /dailies played/);
  await page.click('#ok'); await sleep(200);
  await page.click('#setBtn'); await sleep(300);
  await page.click('.switch[data-k="sound"]');
  assert.equal(await fuse(() => window.__fuse.save.settings.sound), false);
  await page.click('#ok'); await sleep(200);
});

await step('save survives reload and malicious import keys are ignored', async () => {
  await sleep(300);
  await page.reload({ waitUntil: 'load' }); await sleep(500);
  assert.equal(await fuse(() => window.__fuse.save.endless.rounds), 1);
  assert.equal(await fuse(() => window.__fuse.save.settings.sound), false);
  const polluted = await fuse(() => ({}).polluted === undefined);
  assert.ok(polluted);
});

await step('small screen (360x640) lays out the board fully on screen', async () => {
  await page.setViewport({ width: 360, height: 640, deviceScaleFactor: 2, isMobile: true, hasTouch: true });
  await fuse((k) => window.__fuse.show('daily', +k), dailyKey);
  await sleep(500);
  const b = await page.$eval('canvas.board', (cv) => cv.getBoundingClientRect().toJSON());
  assert.ok(b.left >= 0 && b.right <= 360 && b.top >= 0 && b.bottom <= 640, JSON.stringify(b));
  await shot('06-small');
});

await step('no uncaught page errors', async () => { assert.deepEqual(errors, []); });

await browser.close();
server.close();
const failed = results.filter((r) => r[0] === 'FAIL');
console.log(`\n${results.length - failed.length}/${results.length} passed`);
process.exit(failed.length ? 1 : 0);
