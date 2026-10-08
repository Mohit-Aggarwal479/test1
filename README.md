# FUSE — One Tap Chain Puzzle

Every day everyone gets the same board of arrow tiles and **one tap**. The spark you light follows the arrows, flying over burnt tiles, splitting at splitters and blasting through bombs. Score = tiles burnt versus the best possible tap. There is also an Endless mode (10 taps per round, gravity refill) and a monthly **Fuse Map** of every board you played.

Offline, no account, about 2,000 lines of plain JS on Canvas, wrapped with Capacitor for Android.

## Why this game
Built from a multi-agent R&D pass (5 market researchers → 12 concepts → 3-judge panel; FUSE scored 88, top pick of 2 of 3 judges):
- Puzzle is the only large mobile genre still growing (+20% YoY in 2026).
- No Android game combines a one-tap chain with a shared daily board.
- It's built on proven viral patterns: same board for everyone, a spoiler-free emoji share, streaks, and a "Which tile would you tap?" comment-bait image post.
- The daily result is deterministic (integer engine, seeded by date), so scores are comparable across devices.

## Project layout
| Path | What |
|---|---|
| `src/engine.js` | Pure game logic: PRNG, board generation, chain simulation, solver, gravity |
| `src/view.js` | Canvas renderer, chain animation, particles |
| `src/main.js` | Screens, save/streaks, daily/endless/tutorial/map, share cards, ad placements |
| `src/platform.js` | Capacitor wrappers: storage, haptics, share, AdMob (consent, frequency caps) |
| `src/audio.js` | Synthesized WebAudio SFX (no asset files) |
| `www/` | Page shell + bundled `app.js` (built by esbuild) |
| `android/` | Capacitor Android project (SDK 36) |
| `assets/` | Source art for icon and splash |
| `docs/privacy.html` | Privacy policy (serve with GitHub Pages from `/docs`) |

## Develop
```bash
npm install
npm run watch          # http://localhost:8000 — dev server with rebuild on save
npm run build:apk      # debug APK -> android/app/build/outputs/apk/debug/
npm run build:aab      # signed release bundle -> android/app/build/outputs/bundle/release/
```
In a desktop browser, rewarded features are granted for free (marked "(dev)") so every flow can be tested.

## Release checklist
1. **AdMob:** create the app and its ad units. Then:
   - Put the ids in `src/platform.js` (`AD_UNITS`) and set `IS_TESTING = false`.
   - Put the app id in `android/app/src/main/res/values/strings.xml` (`admob_app_id`).
2. **app-ads.txt:** host it at the root of the developer website listed on Play.
3. **Privacy policy:**
   - Enable GitHub Pages (Settings → Pages → branch `main`, folder `/docs`).
   - Replace `CONTACT_EMAIL` in `docs/privacy.html`.
4. **Signing:** `android/keystore.properties` and `android/fuse-upload.jks` exist only on the build PC and are gitignored. **Back both up.** If you lose them, you can't push updates without asking Google to reset the upload key.
5. **Version:** bump `versionCode` and `versionName` in `android/app/build.gradle` for every upload.
6. **Play Console:**
   - Use the listing text below.
   - Content rating: no violence.
   - Target audience: 13+.
   - Data safety: AdMob collects device IDs for advertising.
7. **Closed test:** new personal developer accounts must run a closed test with 12+ testers for 14 days before production.

## Store listing (draft)
- **Title:** Fuse: One Tap Chain Puzzle
- **Short description:** One tap, one chain. Same daily puzzle for everyone. Offline brain teaser.
- **Keywords to work into the description:** one tap puzzle, chain reaction puzzle, daily puzzle, daily brain teaser, arrow puzzle, offline puzzle game, logic puzzle, puzzle streak

## Roadmap
- v1.1: Remove Ads IAP, local daily reminder notification, in-app review prompt, a second theme.
- v1.2: if daily retention is thin, add a second daily mode (Patches/Shikaku-style) instead of more cosmetics.
