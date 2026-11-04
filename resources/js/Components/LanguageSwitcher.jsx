import { router, usePage } from '@inertiajs/react';

/** Til tanlash: o'zbekcha, ruscha, inglizcha. Tanlov foydalanuvchi profilida saqlanadi. */
export default function LanguageSwitcher({ className = '' }) {
    const { locale, locales } = usePage().props;

    return (
        <div className={`grid grid-cols-3 gap-1 rounded-xl bg-bg p-1 text-sm font-bold ${className}`}>
            {Object.entries(locales ?? {}).map(([code, name]) => (
                <button
                    key={code}
                    type="button"
                    lang={code}
                    aria-pressed={code === locale}
                    onClick={() => code !== locale && router.post('/locale', { locale: code }, { preserveScroll: true })}
                    className={`rounded-lg py-2 ${code === locale ? 'bg-card text-g800 shadow-sm' : 'text-muted'}`}
                >
                    {name}
                </button>
            ))}
        </div>
    );
}
