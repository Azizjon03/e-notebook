import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { t } from '../lib/i18n';
import { setTheme } from '../lib/theme';

const btn = 'grid h-9 w-9 place-items-center rounded-lg text-muted ring-1 ring-line';

function Icon({ children }) {
    return (
        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            {children}
        </svg>
    );
}

/** Quyosh/oy: bosilganda yorug' va tungi rejim almashadi. */
export function ThemeToggle() {
    const [dark, setDark] = useState(() => document.documentElement.classList.contains('dark'));

    return (
        <button
            type="button"
            className={btn}
            aria-label={dark ? t("Yorug' rejim") : t('Tungi rejim')}
            title={dark ? t("Yorug' rejim") : t('Tungi rejim')}
            onClick={() => {
                setTheme(dark ? 'light' : 'dark');
                setDark(!dark);
            }}
        >
            {dark ? (
                <Icon>
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
                </Icon>
            ) : (
                <Icon>
                    <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" />
                </Icon>
            )}
        </button>
    );
}

/** Globus: bosilganda uchta til ro'yxati ochiladi. */
export function LanguageMenu() {
    const { locale, locales } = usePage().props;
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return;
        const close = (e) => !ref.current?.contains(e.target) && setOpen(false);
        const esc = (e) => e.key === 'Escape' && setOpen(false);
        document.addEventListener('pointerdown', close);
        document.addEventListener('keydown', esc);
        return () => {
            document.removeEventListener('pointerdown', close);
            document.removeEventListener('keydown', esc);
        };
    }, [open]);

    return (
        <div ref={ref} className="relative">
            <button type="button" className="flex h-9 items-center gap-1 rounded-lg px-2 text-muted ring-1 ring-line" aria-haspopup="menu" aria-expanded={open} aria-label={t('Til')} title={t('Til')} onClick={() => setOpen(!open)}>
                <Icon>
                    <circle cx="12" cy="12" r="9" />
                    <path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" />
                </Icon>
                <span className="text-xs font-bold uppercase">{locale}</span>
            </button>
            {open && (
                <div role="menu" className="absolute right-0 z-20 mt-2 w-40 overflow-hidden rounded-xl bg-card py-1 shadow-lg ring-1 ring-line">
                    {Object.entries(locales ?? {}).map(([code, name]) => (
                        <button
                            key={code}
                            type="button"
                            role="menuitemradio"
                            aria-checked={code === locale}
                            lang={code}
                            onClick={() => {
                                setOpen(false);
                                if (code !== locale) router.post('/locale', { locale: code }, { preserveScroll: true });
                            }}
                            className={`flex w-full items-center justify-between px-4 py-2.5 text-left text-sm font-semibold ${code === locale ? 'text-g700' : 'text-ink'}`}
                        >
                            {name}
                            {code === locale && <span aria-hidden="true">✓</span>}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function HeaderControls({ className = '' }) {
    return (
        <div className={`flex gap-2 ${className}`}>
            <LanguageMenu />
            <ThemeToggle />
        </div>
    );
}
