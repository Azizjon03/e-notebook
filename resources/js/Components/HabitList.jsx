import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { haptic } from '../telegram/webapp';
import { t } from '../lib/i18n';
import NumberInput from './NumberInput';

function CountHabit({ habit, date, canMark }) {
    const [value, setValue] = useState(habit.value ?? '');
    const percent = habit.target ? Math.min(100, Math.round(((habit.value ?? 0) / habit.target) * 100)) : 0;

    const save = (e) => {
        e.preventDefault();
        if (value === '') return;
        router.post(`/habits/${habit.id}/entry`, { date, value: Number(value) }, { preserveScroll: true });
    };

    return (
        <li className="rounded-xl bg-bg p-3">
            <div className="flex items-center justify-between text-sm">
                <span className="font-semibold text-ink">{habit.name}</span>
                <span className={habit.done ? 'font-bold text-g700' : 'text-muted'}>
                    {(habit.value ?? 0).toLocaleString('uz')} / {habit.target.toLocaleString('uz')}
                    {habit.kind === 'duration' && ' daq'}
                </span>
            </div>
            <div className="mt-2 h-1.5 rounded-full bg-white">
                <div className={`h-full rounded-full ${habit.done ? 'bg-g700' : 'bg-g500'}`} style={{ width: `${percent}%` }} />
            </div>
            {canMark && (
                <form onSubmit={save} className="mt-2 flex gap-2">
                    <NumberInput
                        value={value}
                        onChange={setValue}
                        className="min-w-0 flex-1 rounded-lg border border-line bg-white px-2 py-1.5 text-sm"
                        placeholder={habit.kind === 'duration' ? t('Daqiqa') : t('Soni')}
                    />
                    <button className="rounded-lg bg-g700 px-3 text-sm font-bold text-white">{t("Saqlash")}</button>
                </form>
            )}
        </li>
    );
}

function CheckHabit({ habit, date, canMark }) {
    const toggle = () => {
        haptic(habit.done ? 'light' : 'medium');
        router.post(`/habits/${habit.id}/entry`, { date }, { preserveScroll: true });
    };

    return (
        <li className="flex items-center gap-2">
            <button
                type="button"
                disabled={!canMark}
                onClick={toggle}
                className={`flex w-full items-center gap-3 rounded-xl p-3 text-left text-sm font-semibold ${
                    habit.done ? 'bg-g100 text-g800' : 'bg-bg text-ink'
                }`}
            >
                <span className={`grid h-5 w-5 place-items-center rounded-full border-2 ${habit.done ? 'border-g700 bg-g700 text-white' : 'border-line bg-white'}`}>
                    {habit.done && '✓'}
                </span>
                {habit.name}
            </button>
            {habit.key === 'gym' && (
                <Link href="/gym" className="shrink-0 rounded-xl bg-g700 px-3 py-3 text-sm font-bold text-white">
                    {t("Mashg'ulot")}
                </Link>
            )}
        </li>
    );
}

export default function HabitList({ habits, date, canMark }) {
    const others = habits.filter((h) => !['fajr', 'dhuhr', 'asr', 'maghrib', 'isha'].includes(h.key));
    if (!others.length) return null;

    return (
        <section className="mt-4 rounded-2xl bg-white p-4 ring-1 ring-line">
            <div className="mb-3 flex items-baseline justify-between">
                <h2 className="font-bold text-ink">{t("Odatlar")}</h2>
                <Link href="/habits" className="text-sm font-semibold text-g700">
                    {t("Sozlash")}
                </Link>
            </div>
            <ul className="space-y-2">
                {others.map((h) =>
                    h.kind === 'check' ? (
                        <CheckHabit key={h.id} habit={h} date={date} canMark={canMark} />
                    ) : (
                        <CountHabit key={`${h.id}-${date}-${h.value}`} habit={h} date={date} canMark={canMark} />
                    ),
                )}
            </ul>
        </section>
    );
}
