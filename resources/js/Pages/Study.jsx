import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AppLayout from '../Components/AppLayout';
import LevelBar from '../Components/LevelBar';
import { haptic } from '../telegram/webapp';
import { weekdayShort } from '../lib/dates';
import { t } from '../lib/i18n';

function useElapsed(startedAt) {
    const [now, setNow] = useState(Date.now());
    useEffect(() => {
        if (!startedAt) return undefined;
        const id = setInterval(() => setNow(Date.now()), 1000);
        return () => clearInterval(id);
    }, [startedAt]);
    return startedAt ? Math.max(0, Math.floor((now - new Date(startedAt).getTime()) / 1000)) : 0;
}

const pad = (n) => String(n).padStart(2, '0');

function Timer({ running }) {
    const seconds = useElapsed(running.started_at);
    const planned = running.planned_minutes * 60;
    const left = planned - seconds;
    const percent = Math.min(100, (seconds / planned) * 100);
    const shown = Math.abs(left);

    useEffect(() => {
        if (left === 0) haptic('heavy');
    }, [left]);

    return (
        <section className="rounded-2xl bg-g700 p-6 text-center text-white">
            <p className="text-sm font-semibold text-white/80">{running.title || t("O'qish sessiyasi")}</p>
            <p className="mt-2 text-6xl font-extrabold tabular-nums">
                {left < 0 && '+'}
                {pad(Math.floor(shown / 60))}:{pad(shown % 60)}
            </p>
            <p className="mt-1 text-sm text-white/80">{left < 0 ? t('Reja vaqti tugadi, dam oling') : t(':minutes daqiqalik reja', { minutes: running.planned_minutes })}</p>
            <div className="mx-auto mt-4 h-2 max-w-xs rounded-full bg-white/25">
                <div className="h-full rounded-full bg-white" style={{ width: `${percent}%` }} />
            </div>
            <button
                type="button"
                onClick={() => router.post(`/study/${running.id}/stop`, {}, { preserveScroll: true })}
                className="mt-5 rounded-xl bg-white px-6 py-3 font-bold text-g700"
            >
                {t("Tugatish")}
            </button>
        </section>
    );
}

function StartForm({ areas }) {
    const form = useForm({ title: '', area: 'study', planned_minutes: 50 });
    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                haptic('medium');
                form.post('/study/start', { preserveScroll: true });
            }}
            className="space-y-3 rounded-2xl bg-card p-5 ring-1 ring-line"
        >
            <h2 className="text-lg font-bold text-ink">{t("Yangi sessiya")}</h2>
            <input
                value={form.data.title}
                onChange={(e) => form.setData('title', e.target.value)}
                placeholder={t("Nima o'qiysiz? (masalan: DDIA, 5-bob)")}
                maxLength={150}
                className="w-full rounded-xl border border-line bg-bg px-3 py-2.5"
            />
            <div className="flex gap-2">
                <select value={form.data.area} onChange={(e) => form.setData('area', e.target.value)} className="flex-1 rounded-lg border border-line bg-card px-2 py-2 text-sm">
                    {areas.map((a) => (
                        <option key={a.key} value={a.key}>
                            {a.name}
                        </option>
                    ))}
                </select>
                <div className="flex rounded-lg bg-bg p-1 text-sm font-bold">
                    {[25, 50, 90].map((m) => (
                        <button
                            key={m}
                            type="button"
                            onClick={() => form.setData('planned_minutes', m)}
                            className={`rounded-md px-3 py-1 ${form.data.planned_minutes === m ? 'bg-card text-g800 shadow-sm' : 'text-muted'}`}
                        >
                            {t(':m daq', { m })}
                        </button>
                    ))}
                </div>
            </div>
            <button className="w-full rounded-xl bg-g700 py-3 font-bold text-white" disabled={form.processing}>
                {t("Boshlash")}
            </button>
        </form>
    );
}

export default function Study({ running, today, week, level, pointsToday, recent, areas }) {
    const maxMinutes = Math.max(50, ...week.map((d) => d.minutes));

    return (
        <AppLayout>
            <Head title={t("O'qish")} />
            <LevelBar level={level} pointsToday={pointsToday} />

            <div className="mt-4">{running ? <Timer running={running} /> : <StartForm areas={areas} />}</div>

            <section className="mt-4 rounded-2xl bg-card p-4 ring-1 ring-line">
                <div className="mb-3 flex items-baseline justify-between">
                    <h2 className="font-bold text-ink">{t("Oxirgi 7 kun")}</h2>
                    <Link href="/stats" className="text-sm font-semibold text-g700">
                        {t("To'liq statistika")}
                    </Link>
                </div>
                <div className="flex h-28 items-end gap-2">
                    {week.map((d) => (
                        <div key={d.date} className="flex flex-1 flex-col items-center gap-1">
                            <span className="text-[10px] font-semibold text-muted">{d.minutes || ''}</span>
                            <div className="w-full rounded-md bg-g500" style={{ height: `${Math.max(3, (d.minutes / maxMinutes) * 80)}px`, opacity: d.minutes ? 1 : 0.2 }} />
                            <span className="text-[11px] font-bold text-muted">{weekdayShort(d.date)}</span>
                        </div>
                    ))}
                </div>
            </section>

            {today.length > 0 && (
                <section className="mt-4 rounded-2xl bg-card p-4 ring-1 ring-line">
                    <h2 className="mb-2 font-bold text-ink">{t("Bugungi sessiyalar")}</h2>
                    <ul className="divide-y divide-line text-sm">
                        {today.map((s) => (
                            <li key={s.id} className="flex items-center justify-between gap-2 py-2">
                                <span className="min-w-0 truncate text-ink">
                                    <span className="text-muted">{s.time}</span> {s.title}
                                </span>
                                <span className="shrink-0 font-semibold text-ink">
                                    {t(':m daq', { m: s.minutes })} · <span className="text-g700">+{s.points}</span>
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {recent.length > 0 && (
                <section className="mt-4 rounded-2xl bg-card p-4 ring-1 ring-line">
                    <h2 className="mb-2 font-bold text-ink">{t("Oxirgi ballar")}</h2>
                    <ul className="divide-y divide-line text-sm">
                        {recent.map((r, i) => (
                            <li key={i} className="flex justify-between py-2">
                                <span className="text-ink">
                                    {r.label} <span className="text-muted">· {r.at}</span>
                                </span>
                                <span className="font-bold text-g700">+{r.points}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </AppLayout>
    );
}
