// Telegram Mini App SDK uchun yupqa o'ram.
// Oddiy brauzerda (web) window.Telegram bo'lmaydi, shunda hamma funksiya jim o'tadi.

export function webApp() {
    return window.Telegram?.WebApp ?? null;
}

export function isMiniApp() {
    return Boolean(webApp()?.initData);
}

export function initTelegram() {
    const app = webApp();
    if (!app) return;
    app.ready();
    app.expand();
    document.documentElement.dataset.telegram = 'true';
}

export function haptic(type = 'light') {
    webApp()?.HapticFeedback?.impactOccurred(type);
}
