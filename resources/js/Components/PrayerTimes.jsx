import { router } from '@inertiajs/react';
import { haptic } from '../telegram/webapp';
import { t } from '../lib/i18n';

/**
 * Namoz vaqtlari va namoz odatlari bitta kartada: chipni bosib namozni "o'qildi" deb belgilash mumkin.
 */
export default function PrayerTimes({ prayers, habits, date, now, isToday, canMark }) {
    const byKey = Object.fromEntries((habits ?? []).map((h) => [h.key, h]));
    const rows = prayers ?? ['fajr', 'sunrise', 'dhuhr', 'asr', 'maghrib', 'isha'].map((key) => ({ key, name: byKey[key]?.name, time: null }));
    const visible = rows.filter((p) => p.key === 'sunrise' ? Boolean(prayers) : Boolean(byKey[p.key]));

    if (!visible.length) {
        return null;
    }

    const next = isToday && prayers ? prayers.find((p) => p.key !== 'sunrise' && p.time > now) : null;
    const prayed = visible.filter((p) => byKey[p.key]?.done).length;
    const total = visible.filter((p) => byKey[p.key]).length;

    const mark = (habit) => {
        if (!habit || !canMark) return;
        haptic(habit.done ? 'light' : 'medium');
        router.post(`/habits/${habit.id}/entry`, { date }, { preserveScroll: true, preserveState: true });
    };

    return (
        <section className="mt-4 rounded-2xl bg-white p-4 ring-1 ring-line">
            <div className="mb-3 flex items-baseline justify-between gap-2">
                <h2 className="font-bold text-ink">{t('Namoz')}</h2>
                <span className="text-sm font-semibold text-muted">
                    {next ? (
                        <span className="text-g700">
                            {t('Keyingisi: :name :time', { name: next.name, time: next.time })}
                        </span>
                    ) : (
                        t(":prayed/:total o'qildi", { prayed, total })
                    )}
                </span>
            </div>
            <ul className="grid grid-cols-6 gap-1 text-center">
                {visible.map((p) => {
                    const habit = byKey[p.key];
                    const done = habit?.done;
                    const active = next?.key === p.key;
                    const tone = done ? 'bg-g700 text-white' : active ? 'bg-g100 ring-2 ring-g500' : 'bg-bg';
                    return (
                        <li key={p.key}>
                            <button
                                type="button"
                                disabled={!habit || !canMark}
                                onClick={() => mark(habit)}
                                aria-pressed={done ? 'true' : 'false'}
                                aria-label={habit ? `${p.name}: ${done ? t("o'qildi") : t("o'qilmadi")}` : p.name}
                                className={`w-full rounded-xl py-2 ${tone} disabled:cursor-default`}
                            >
                                <span className={`block text-[11px] font-semibold ${done ? 'text-white/80' : 'text-muted'}`}>{p.name}</span>
                                <span className={`block text-sm font-bold ${done ? 'text-white' : p.key === 'sunrise' ? 'text-muted' : 'text-ink'}`}>
                                    {p.time ?? (done ? '✓' : '—')}
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ul>
            {canMark && <p className="mt-2 text-xs text-muted">{t("O'qilgan namozni belgilash uchun ustiga bosing.")}</p>}
        </section>
    );
}
