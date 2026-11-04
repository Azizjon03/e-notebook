import { Link, usePage } from '@inertiajs/react';

export default function AppLayout({ children }) {
    const { auth } = usePage().props;

    return (
        <div className="mx-auto min-h-screen max-w-md px-4 py-6">
            <header className="mb-5 flex items-center gap-2">
                <span className="grid h-9 w-9 place-items-center rounded-xl bg-g700 font-extrabold text-white">e</span>
                <span className="text-lg font-bold text-ink">e-notebook</span>
                {auth?.user?.is_admin && (
                    <Link href="/admin" className="ml-auto rounded-lg px-3 py-1.5 text-sm font-semibold text-g700 ring-1 ring-line">
                        Admin
                    </Link>
                )}
            </header>
            <main>{children}</main>
        </div>
    );
}
