import { useState } from 'react';
import { t, tk } from '../lib/i18n';
import { getTheme, setTheme } from '../lib/theme';

const OPTIONS = [
    ['light', tk("Yorug'")],
    ['dark', tk('Tungi')],
    ['system', tk('Tizim')],
];

export default function ThemeSwitcher({ className = '' }) {
    const [theme, set] = useState(getTheme);

    return (
        <div className={`grid grid-cols-3 gap-1 rounded-xl bg-bg p-1 text-sm font-bold ${className}`}>
            {OPTIONS.map(([value, label]) => (
                <button
                    key={value}
                    type="button"
                    aria-pressed={theme === value}
                    onClick={() => {
                        setTheme(value);
                        set(value);
                    }}
                    className={`rounded-lg py-2 ${theme === value ? 'bg-card text-g800 shadow-sm' : 'text-muted'}`}
                >
                    {t(label)}
                </button>
            ))}
        </div>
    );
}
