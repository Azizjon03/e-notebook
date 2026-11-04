export default function AppLayout({ children }) {
    return (
        <div className="mx-auto min-h-screen max-w-md px-4 py-6">
            <header className="mb-5 flex items-center gap-2">
                <span className="grid h-9 w-9 place-items-center rounded-xl bg-g700 font-extrabold text-white">e</span>
                <span className="text-lg font-bold text-ink">e-notebook</span>
            </header>
            <main>{children}</main>
        </div>
    );
}
