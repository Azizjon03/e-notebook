import { Link, usePage } from '@inertiajs/react';

const TABS = [
    { href: '/', label: 'Bugun', match: (url) => url === '/' || url.startsWith('/?') },
    { href: '/calendar', label: 'Kalendar', match: (url) => url.startsWith('/calendar') },
    { href: '/habits', label: 'Odatlar', match: (url) => url.startsWith('/habits') },
    { href: '/study', label: "O'qish", match: (url) => url.startsWith('/study') },
    { href: '/books', label: 'Kitoblar', match: (url) => url.startsWith('/books') },
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
                    <Link href="/settings" aria-label="Sozlamalar" className="grid h-9 w-9 place-items-center rounded-lg text-muted ring-1 ring-line">
                        <svg viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2">
                            <circle cx="12" cy="12" r="3" />
                            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z" />
                        </svg>
                    </Link>
                </div>
            </header>
            <main>{children}</main>

            <nav className="fixed inset-x-0 bottom-0 border-t border-line bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur">
                <div className="mx-auto flex max-w-md">
                    {TABS.map((tab) => {
                        const active = tab.match(url);
                        return (
                            <Link
                                key={tab.href}
                                href={tab.href}
                                className={`flex-1 py-3 text-center text-[13px] font-bold ${active ? 'text-g700' : 'text-muted'}`}
                            >
                                <span className={`mx-auto mb-1 block h-1 w-8 rounded-full ${active ? 'bg-g700' : 'bg-transparent'}`} />
                                {tab.label}
                            </Link>
                        );
                    })}
                </div>
            </nav>
        </div>
    );
}
