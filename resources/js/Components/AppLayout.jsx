import { Link, router, usePage } from '@inertiajs/react';
import { t, tk } from '../lib/i18n';
import { initials } from '../lib/initials';
import HeaderControls from './HeaderControls';

const TABS = [
    { href: '/', icon: <><path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" /></>, label: tk('Bugun'), match: (url) => url === '/' || url.startsWith('/?') },
    { href: '/calendar', icon: <><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></>, label: tk('Kalendar'), match: (url) => url.startsWith('/calendar') },
    { href: '/habits', icon: <><path d="M17 1l4 4-4 4" /><path d="M3 11V9a4 4 0 0 1 4-4h14" /><path d="M7 23l-4-4 4-4" /><path d="M21 13v2a4 4 0 0 1-4 4H3" /></>, label: tk('Odatlar'), match: (url) => url.startsWith('/habits') },
    { href: '/study', icon: <><circle cx="12" cy="13" r="8" /><path d="M12 9v4l2 2M9 2h6" /></>, label: tk("O'qish"), match: (url) => url.startsWith('/study') },
    { href: '/books', icon: <><path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2z" /><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z" /></>, label: tk('Kitoblar'), match: (url) => url.startsWith('/books') },
];

export default function AppLayout({ children }) {
    const { url, props } = usePage();
    const { auth } = props;

    return (
        <div className="mx-auto min-h-screen max-w-md px-4 pt-6 pb-24">
            <header className="mb-5 flex items-center gap-2">
                <span className="grid h-9 w-9 place-items-center rounded-xl bg-g700 font-extrabold text-white">e</span>
                <span className="text-lg font-bold text-ink">e-notebook</span>
                <div className="ml-auto flex gap-2">
                    {auth?.user?.is_admin && (
                        <Link href="/admin" className="rounded-lg px-3 py-1.5 text-sm font-semibold text-g700 ring-1 ring-line">
                            Admin
                        </Link>
                    )}
                    <HeaderControls />
                    <Link
                        href="/profile"
                        aria-label={t('Profil')}
                        className={`grid h-9 w-9 place-items-center rounded-full text-sm font-extrabold ${url.startsWith('/profile') ? 'bg-g700 text-white' : 'bg-g100 text-g800'}`}
                    >
                        {initials(auth?.user?.first_name, auth?.user?.last_name)}
                    </Link>
                    <Link href="/settings" aria-label={t("Sozlamalar")} className="grid h-9 w-9 place-items-center rounded-lg text-muted ring-1 ring-line">
                        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                            <circle cx="12" cy="12" r="3" />
                            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z" />
                        </svg>
                    </Link>
                </div>
            </header>
            {auth?.user?.is_demo && (
                <div className="mb-4 flex items-center gap-3 rounded-2xl bg-gold/15 px-4 py-3 text-sm">
                    <p className="min-w-0 flex-1 font-semibold text-ink">{t("Bu demo. Biror narsa qo'shish uchun ro'yxatdan o'ting.")}</p>
                    <button type="button" onClick={() => router.post('/demo/exit')} className="shrink-0 rounded-lg bg-g700 px-3 py-1.5 font-bold text-white">
                        {t("Ro'yxatdan o'tish")}
                    </button>
                </div>
            )}
            <main>{children}</main>

            <nav className="fixed inset-x-0 bottom-0 border-t border-line bg-card/95 pb-[env(safe-area-inset-bottom)] backdrop-blur">
                <div className="mx-auto flex max-w-md">
                    {TABS.map((tab) => {
                        const active = tab.match(url);
                        return (
                            <Link
                                key={tab.href}
                                href={tab.href}
                                aria-label={t(tab.label)}
                                title={t(tab.label)}
                                aria-current={active ? 'page' : undefined}
                                className={`flex flex-1 flex-col items-center py-2.5 ${active ? 'text-g700' : 'text-muted'}`}
                            >
                                <span className={`mb-1.5 block h-1 w-8 rounded-full ${active ? 'bg-g700' : 'bg-transparent'}`} />
                                <svg viewBox="0 0 24 24" className="h-6 w-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                    {tab.icon}
                                </svg>
                            </Link>
                        );
                    })}
                </div>
            </nav>
        </div>
    );
}
