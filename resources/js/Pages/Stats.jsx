import { Head, Link } from '@inertiajs/react';
import AppLayout from '../Components/AppLayout';
import LevelBar from '../Components/LevelBar';
import { parseDate, weekdayShort } from '../lib/dates';
import { t } from '../lib/i18n';

function Tile({ label, value, hint }) {
    return (
        <div className="rounded-2xl bg-white p-4 ring-1 ring-line">
            <p className="text-xs font-semibold text-muted">{label}</p>
            <p className="mt-1 text-2xl font-extrabold text-ink">{value}</p>
            {hint && <p className="text-xs text-muted">{hint}</p>}
        </div>
    );
}

function Bars({ title, days, value, max, unit, color = 'bg-g500', range }) {
    const top = Math.max(max ?? 0, ...days.map(value), 1);
    return (
        <section className="mt-4 rounded-2xl bg-white p-4 ring-1 ring-line">
            <h2 className="mb-3 font-bold text-ink">{title}</h2>
            <div className="flex h-32 items-end gap-[3px]">
                {days.map((d) => {
                    const v = value(d);
                    return (
                        <div key={d.date} className="flex h-full flex-1 flex-col justify-end" title={`${d.date}: ${v} ${unit}`}>
                            <div className={`w-full rounded-sm ${v ? color : 'bg-line'}`} style={{ height: `${Math.max(3, (v / top) * 100)}%` }} />
                        </div>
                    );
                })}
            </div>
            <div className="mt-1 flex gap-[3px] text-center text-[10px] font-semibold text-muted">
                {days.map((d, i) => (
                    <span key={d.date} className="flex-1">
                        {range === 7 ? weekdayShort(d.date) : i % 5 === 0 ? parseDate(d.date).getUTCDate() : ''}
                    </span>
                ))}
            </div>
        </section>
    );
}

export default function Stats({ range, days, summary, habits, level }) {
    const tab = (r) => `flex-1 rounded-lg py-1.5 text-center text-sm font-bold ${range === r ? 'bg-white text-g800 shadow-sm' : 'text-muted'}`;

    return (
        <AppLayout>
            <Head title={t("Statistika")} />
            <h1 className="text-2xl font-extrabold text-ink">{t("Statistika")}</h1>
            <div className="mt-3 flex rounded-xl bg-g100 p-1">
                <Link href="/stats?range=7" className={tab(7)} preserveScroll>
                    {t("7 kun")}
                </Link>
                <Link href="/stats?range=30" className={tab(30)} preserveScroll>
                    {t("30 kun")}
                </Link>
            </div>

            <div className="mt-4">
                <LevelBar level={level} />
            </div>

            <div className="mt-4 grid grid-cols-2 gap-2">
                <Tile label={t("Vazifalar")} value={summary.tasks_rate === null ? '—' : `${summary.tasks_rate}%`} hint={t(':count ta bajarildi', { count: summary.tasks_done })} />
                <Tile label={t("Namoz")} value={`${summary.prayers}/${summary.prayers_possible}`} />
                <Tile label={t("O'qish")} value={t(':h soat', { h: (summary.study_minutes / 60).toFixed(1) })} hint={t(':m daqiqa', { m: summary.study_minutes })} />
                <Tile label={t("Ball")} value={`+${summary.points}`} hint={summary.pages ? t(':pages bet kitob', { pages: summary.pages }) : null} />
            </div>

            <Bars title={t("Bajarilgan vazifalar")} days={days} value={(d) => d.tasks_done} unit={t("ta")} range={range} />
            <Bars title={t("Namoz")} days={days} value={(d) => d.prayers} max={5} unit={t("ta")} color="bg-g700" range={range} />
            <Bars title={t("O'qish, daqiqa")} days={days} value={(d) => d.study} unit={t("daq")} color="bg-blue" range={range} />
            <Bars title={t("Ball")} days={days} value={(d) => d.points} unit={t("ball")} color="bg-gold" range={range} />

            {habits.length > 0 && (
                <section className="mt-4 rounded-2xl bg-white p-4 ring-1 ring-line">
                    <h2 className="mb-3 font-bold text-ink">{t("Odatlar")}</h2>
                    <ul className="space-y-3">
                        {habits.map((h) => {
                            const pct = h.scheduled ? Math.round((h.done / h.scheduled) * 100) : 0;
                            return (
                                <li key={h.name}>
                                    <div className="flex justify-between text-sm">
                                        <span className="font-semibold text-ink">{h.name}</span>
                                        <span className="text-muted">
                                            {t(':done/:scheduled kun', { done: h.done, scheduled: h.scheduled })}
                                        </span>
                                    </div>
                                    <div className="mt-1 h-2 rounded-full bg-bg">
                                        <div className="h-full rounded-full bg-g700" style={{ width: `${pct}%` }} />
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                </section>
            )}
        </AppLayout>
    );
}
