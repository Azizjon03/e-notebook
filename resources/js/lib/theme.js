// Mavzu: 'light' | 'dark' | 'system'. Tanlov shu qurilmada saqlanadi.
export const THEMES = ['light', 'dark', 'system'];

const media = () => window.matchMedia('(prefers-color-scheme: dark)');

export function getTheme() {
    try {
        const saved = localStorage.getItem('theme');
        return THEMES.includes(saved) ? saved : 'system';
    } catch {
        return 'system';
    }
}

export function applyTheme(theme = getTheme()) {
    const dark = theme === 'dark' || (theme === 'system' && media().matches);
    document.documentElement.classList.toggle('dark', dark);
    window.Telegram?.WebApp?.setHeaderColor?.(dark ? '#0e1511' : '#f4f8f5');
}

export function setTheme(theme) {
    try {
        localStorage.setItem('theme', theme);
    } catch {
        // Saqlab bo'lmasa ham shu sahifada ishlaydi.
    }
    applyTheme(theme);
}

/** Tizim mavzusi o'zgarsa ("Tizim" tanlangan bo'lsa) darhol moslashadi. */
export function watchSystemTheme() {
    media().addEventListener?.('change', () => getTheme() === 'system' && applyTheme('system'));
}
