// Platform layer: wraps Capacitor plugins with safe fallbacks so the game runs
// identically in a desktop browser (dev) and inside the Android WebView (prod).
import { Capacitor } from '@capacitor/core';
import { Preferences } from '@capacitor/preferences';
import { Haptics, ImpactStyle, NotificationType } from '@capacitor/haptics';
import { Share } from '@capacitor/share';
import { StatusBar, Style } from '@capacitor/status-bar';
import { SplashScreen } from '@capacitor/splash-screen';
import {
  AdMob,
  BannerAdPosition,
  BannerAdSize,
  BannerAdPluginEvents,
  InterstitialAdPluginEvents,
  RewardAdPluginEvents,
} from '@capacitor-community/admob';

export const isNative = Capacitor.isNativePlatform();

// ---------- Ad unit IDs ----------
// Google's official TEST ids. Replace with real AdMob ids before release
// (also replace admob_app_id in android/app/src/main/res/values/strings.xml).
export const AD_UNITS = {
  banner: 'ca-app-pub-3940256099942544/6300978111',
  interstitial: 'ca-app-pub-3940256099942544/1033173712',
  rewarded: 'ca-app-pub-3940256099942544/5224354917',
};
const IS_TESTING = true; // flip to false with real ids

// ---------- Storage ----------
const memCache = new Map();
export const storage = {
  async get(key, fallback = null) {
    try {
      if (isNative) {
        const { value } = await Preferences.get({ key });
        return value == null ? fallback : JSON.parse(value);
      }
      const v = localStorage.getItem(key);
      return v == null ? fallback : JSON.parse(v);
    } catch {
      return memCache.has(key) ? memCache.get(key) : fallback;
    }
  },
  async set(key, value) {
    memCache.set(key, value);
    try {
      const s = JSON.stringify(value);
      if (isNative) await Preferences.set({ key, value: s });
      else localStorage.setItem(key, s);
    } catch { /* storage unavailable: keep in memory */ }
  },
};

// ---------- Haptics ----------
export const haptics = {
  async light() { try { if (isNative) await Haptics.impact({ style: ImpactStyle.Light }); else navigator.vibrate?.(8); } catch {} },
  async medium() { try { if (isNative) await Haptics.impact({ style: ImpactStyle.Medium }); else navigator.vibrate?.(15); } catch {} },
  async heavy() { try { if (isNative) await Haptics.impact({ style: ImpactStyle.Heavy }); else navigator.vibrate?.(30); } catch {} },
  async success() { try { if (isNative) await Haptics.notification({ type: NotificationType.Success }); else navigator.vibrate?.([10, 40, 10]); } catch {} },
  async error() { try { if (isNative) await Haptics.notification({ type: NotificationType.Error }); else navigator.vibrate?.([30, 30, 30]); } catch {} },
};

// ---------- Share ----------
export async function share({ title, text, url }) {
  try {
    if (isNative) { await Share.share({ title, text, url, dialogTitle: title }); return true; }
    if (navigator.share) { await navigator.share({ title, text, url }); return true; }
    await navigator.clipboard?.writeText(text + (url ? ' ' + url : ''));
    return 'copied';
  } catch { return false; }
}

// ---------- System UI ----------
export async function setupSystemUI(bg = '#111111') {
  if (!isNative) return;
  try { await StatusBar.setBackgroundColor({ color: bg }); await StatusBar.setStyle({ style: Style.Dark }); } catch {}
  try { await SplashScreen.hide(); } catch {}
}

// ---------- Ads ----------
// Policy: interstitial at most once per INTERSTITIAL_MIN_GAP_MS and never within
// the first few games; rewarded always user-initiated; banner only on menus.
const INTERSTITIAL_MIN_GAP_MS = 150_000;
let lastInterstitialAt = 0;
let interstitialReady = false;
let rewardedReady = false;
let adsRemoved = false;
let initialized = false;
let canRequestAds = false;
let rewardInFlight = null;
let bannerVisible = false;
let bannerHeight = 0;
const listeners = { bannerSize: [] };

export const ads = {
  get enabled() { return isNative && initialized && canRequestAds && !adsRemoved; },
  get bannerHeight() { return bannerVisible ? bannerHeight : 0; },
  onBannerSize(fn) { listeners.bannerSize.push(fn); },

  async init() {
    adsRemoved = !!(await storage.get('adsRemoved', false));
    if (!isNative || adsRemoved) return;
    try {
      await AdMob.initialize({ initializeForTesting: IS_TESTING, testingDevices: [] });
      initialized = true;
      // UMP consent (EU/UK). Fails soft outside consent regions.
      try {
        let info = await AdMob.requestConsentInfo();
        if (info.isConsentFormAvailable && info.status === 'REQUIRED') info = await AdMob.showConsentForm();
        canRequestAds = !!info.canRequestAds;
      } catch { canRequestAds = false; }
      AdMob.addListener(InterstitialAdPluginEvents.Loaded, () => { interstitialReady = true; });
      AdMob.addListener(InterstitialAdPluginEvents.FailedToLoad, () => { interstitialReady = false; setTimeout(() => ads.preloadInterstitial(), 30_000); });
      AdMob.addListener(InterstitialAdPluginEvents.Dismissed, () => { interstitialReady = false; ads.preloadInterstitial(); });
      AdMob.addListener(InterstitialAdPluginEvents.FailedToShow, () => { interstitialReady = false; ads.preloadInterstitial(); });
      AdMob.addListener(RewardAdPluginEvents.FailedToShow, () => { rewardedReady = false; ads.preloadRewarded(); });
      AdMob.addListener(RewardAdPluginEvents.Loaded, () => { rewardedReady = true; });
      AdMob.addListener(RewardAdPluginEvents.FailedToLoad, () => { rewardedReady = false; setTimeout(() => ads.preloadRewarded(), 30_000); });
      AdMob.addListener(RewardAdPluginEvents.Dismissed, () => { rewardedReady = false; ads.preloadRewarded(); });
      AdMob.addListener(BannerAdPluginEvents.SizeChanged, (s) => { bannerHeight = s?.height || 0; listeners.bannerSize.forEach(f => f(ads.bannerHeight)); });
      ads.preloadInterstitial();
      ads.preloadRewarded();
    } catch (e) { initialized = false; }
  },

  async preloadInterstitial() {
    if (!ads.enabled || interstitialReady) return;
    try { await AdMob.prepareInterstitial({ adId: AD_UNITS.interstitial, isTesting: IS_TESTING }); } catch { interstitialReady = false; }
  },
  async preloadRewarded() {
    if (!ads.enabled || rewardedReady) return;
    try { await AdMob.prepareRewardVideoAd({ adId: AD_UNITS.rewarded, isTesting: IS_TESTING }); } catch { rewardedReady = false; }
  },

  /** Show an interstitial if allowed by frequency cap. Resolves when dismissed (or immediately if skipped). */
  async maybeShowInterstitial({ gamesPlayed = 0, minGames = 3 } = {}) {
    if (!ads.enabled || !interstitialReady) return false;
    if (gamesPlayed < minGames) return false;
    const now = Date.now();
    if (now - lastInterstitialAt < INTERSTITIAL_MIN_GAP_MS) return false;
    lastInterstitialAt = now;
    return new Promise(async (resolve) => {
      let done = false;
      const finish = (v) => { if (!done) { done = true; resolve(v); } };
      const h1 = await AdMob.addListener(InterstitialAdPluginEvents.Dismissed, () => { h1.remove(); h2.remove(); finish(true); });
      const h2 = await AdMob.addListener(InterstitialAdPluginEvents.FailedToShow, () => { h1.remove(); h2.remove(); finish(false); });
      interstitialReady = false;
      try { await AdMob.showInterstitial(); } catch { h1.remove(); h2.remove(); finish(false); ads.preloadInterstitial(); }
      setTimeout(() => finish(false), 60_000);
    });
  },

  get rewardedAvailable() { return ads.enabled && rewardedReady; },

  /** Show rewarded ad; resolves true only if the user earned the reward. */
  async showRewarded() {
    if (rewardInFlight) return rewardInFlight;
    if (!ads.enabled || !rewardedReady) return false;
    rewardedReady = false; // an ad object can only be shown once
    rewardInFlight = new Promise(async (resolve) => {
      let rewarded = false, done = false;
      let hR, hD, hF;
      const finish = () => { if (!done) { done = true; rewardInFlight = null; hR?.remove(); hD?.remove(); hF?.remove(); resolve(rewarded); } };
      hR = await AdMob.addListener(RewardAdPluginEvents.Rewarded, () => { rewarded = true; });
      hD = await AdMob.addListener(RewardAdPluginEvents.Dismissed, () => setTimeout(finish, 50));
      hF = await AdMob.addListener(RewardAdPluginEvents.FailedToShow, finish);
      try { await AdMob.showRewardVideoAd(); } catch { finish(); ads.preloadRewarded(); }
      setTimeout(finish, 120_000);
    });
    return rewardInFlight;
  },

  async showBanner() {
    if (!ads.enabled || bannerVisible) return;
    try {
      await AdMob.showBanner({ adId: AD_UNITS.banner, adSize: BannerAdSize.ADAPTIVE_BANNER, position: BannerAdPosition.BOTTOM_CENTER, margin: 0, isTesting: IS_TESTING });
      bannerVisible = true;
    } catch {}
  },
  async hideBanner() {
    if (!isNative || !bannerVisible) return;
    // hideBanner leaves a paused view that a later showBanner won't revive; destroy it instead
    try { await AdMob.removeBanner(); } catch {}
    bannerVisible = false;
    listeners.bannerSize.forEach(f => f(0));
  },

  /** Called after a successful "remove ads" purchase (hook for IAP). */
  async setAdsRemoved(v) {
    adsRemoved = !!v;
    await storage.set('adsRemoved', adsRemoved);
    if (adsRemoved) { try { await AdMob.removeBanner(); } catch {} bannerVisible = false; }
  },
};
