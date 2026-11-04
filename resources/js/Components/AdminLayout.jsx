import { Link, usePage } from '@inertiajs/react';

const NAV = [
    { href: '/admin', label: 'Umumiy' },
    { href: '/admin/bot', label: 'Bot sozlamalari' },
];

export default function AdminLayout({ title, children }) {
    const { url, props } = usePage();
    const { flash } = props;

    return (
        <div className="min-h-screen bg-bg">
            <header className="border-b border-line bg-card">
                <div className="mx-auto flex max-w-5xl flex-wrap items-center gap-4 px-4 py-3">
                    <span className="flex items-center gap-2 font-bold text-ink">
                        <span className="grid h-8 w-8 place-items-center rounded-lg bg-g700 font-extrabold text-white">e</span>
                        Admin
                    </span>
                    <nav className="flex gap-1">
                        {NAV.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`rounded-lg px-3 py-1.5 text-sm font-semibold ${
                                    url === item.href ? 'bg-g100 text-g800' : 'text-muted hover:text-ink'
                                }`}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>
                    <Link href="/" className="ml-auto text-sm font-semibold text-g700">
                        Ilovaga qaytish
                    </Link>
                </div>
            </header>
            <main className="mx-auto max-w-5xl px-4 py-6">
                <h1 className="mb-5 text-2xl font-extrabold text-ink">{title}</h1>
                {flash?.success && (
                    <p className="mb-4 rounded-xl bg-g100 px-4 py-3 text-sm font-semibold text-g800">{flash.success}</p>
                )}
                {flash?.error && (
                    <p className="mb-4 rounded-xl bg-red/10 px-4 py-3 text-sm font-semibold text-red">{flash.error}</p>
                )}
                {children}
            </main>
        </div>
    );
}

export function Card({ title, children, className = '' }) {
    return (
        <section className={`rounded-2xl bg-card p-5 ring-1 ring-line ${className}`}>
            {title && <h2 className="mb-4 text-lg font-bold text-ink">{title}</h2>}
            {children}
        </section>
    );
}
