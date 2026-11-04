import { Head, Link } from '@inertiajs/react';
import AppLayout from '../Components/AppLayout';
import TaskItem from '../Components/TaskItem';
import { addDays, addMonths, formatShort, monthTitle, parseDate, weekdayShort } from '../lib/dates';

const HEAD = ['Du', 'Se', 'Cho', 'Pa', 'Ju', 'Sha', 'Ya'];

function Switcher({ view, anchor }) {
    const cls = (v) => `flex-1 rounded-lg py-1.5 text-center text-sm font-bold ${view === v ? 'bg-white text-g800 shadow-sm' : 'text-muted'}`;
    return (
        <div className="flex rounded-xl bg-g100 p-1">
            <Link href={`/calendar?view=month&date=${anchor}`} className={cls('month')} preserveScroll>
                Oy
            </Link>
            <Link href={`/calendar?view=week&date=${anchor}`} className={cls('week')} preserveScroll>
                Hafta
            </Link>
        </div>
    );
}

function Nav({ title, prev, next }) {
    return (
        <div className="mt-4 flex items-center justify-between">
            <Link href={prev} preserveScroll className="rounded-lg px-3 py-1 text-xl text-ink" aria-label="Oldingi">
                ‹
            </Link>
            <h1 className="text-xl font-extrabold text-ink">{title}</h1>
            <Link href={next} preserveScroll className="rounded-lg px-3 py-1 text-xl text-ink" aria-label="Keyingi">
                ›
            </Link>
        </div>
    );
}

function MonthView({ days, anchor, month, today }) {
    return (
        <>
            <Nav
                title={monthTitle(anchor)}
                prev={`/calendar?view=month&date=${addMonths(anchor, -1)}`}
                next={`/calendar?view=month&date=${addMonths(anchor, 1)}`}
            />
            <div className="mt-3 grid grid-cols-7 gap-1 text-center text-xs font-bold text-muted">
                {HEAD.map((d) => (
                    <span key={d}>{d}</span>
                ))}
            </div>
            <div className="mt-1 grid grid-cols-7 gap-1">
                {days.map((day) => {
                    const inMonth = day.date.startsWith(month);
                    const isToday = day.date === today;
                    const allDone = day.total > 0 && day.done === day.total;
                    return (
                        <Link
                            key={day.date}
                            href={`/?date=${day.date}`}
                            className={`flex aspect-square flex-col items-center justify-start rounded-xl pt-1.5 text-sm ${
                                isToday ? 'bg-g700 text-white' : inMonth ? 'bg-white text-ink ring-1 ring-line' : 'text-muted/50'
                            }`}
                        >
                            <span className="font-bold">{parseDate(day.date).getUTCDate()}</span>
                            {day.total > 0 && (
                                <span
                                    className={`mt-auto mb-1.5 rounded-full px-1.5 text-[10px] font-bold ${
                                        isToday
                                            ? 'bg-white/25'
                                            : allDone
                                              ? 'bg-g100 text-g800'
                                              : day.date < today
                                                ? 'bg-gold/15 text-gold'
                                                : 'bg-bg text-muted'
                                    }`}
                                >
                                    {day.done}/{day.total}
                                </span>
                            )}
                        </Link>
                    );
                })}
            </div>
        </>
    );
}

function WeekView({ days, anchor, today }) {
    const first = days[0].date;
    const last = days[days.length - 1].date;
    return (
        <>
            <Nav
                title={`${formatShort(first)} – ${formatShort(last)}`}
                prev={`/calendar?view=week&date=${addDays(anchor, -7)}`}
                next={`/calendar?view=week&date=${addDays(anchor, 7)}`}
            />
            <div className="mt-3 space-y-4">
                {days.map((day) => (
                    <section key={day.date}>
                        <Link href={`/?date=${day.date}`} className="mb-2 flex items-baseline gap-2">
                            <span className={`font-extrabold ${day.date === today ? 'text-g700' : 'text-ink'}`}>
                                {weekdayShort(day.date)}, {formatShort(day.date)}
                            </span>
                            {day.total > 0 && (
                                <span className="text-xs font-semibold text-muted">
                                    {day.done}/{day.total}
                                </span>
                            )}
                        </Link>
                        {day.tasks.length ? (
                            <ul className="space-y-2">
                                {day.tasks.map((t) => (
                                    <TaskItem key={t.id} task={t} />
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-muted">Vazifa yo'q</p>
                        )}
                    </section>
                ))}
            </div>
        </>
    );
}

export default function Calendar({ view, anchor, month, today, days, recurrences }) {
    return (
        <AppLayout>
            <Head title="Kalendar" />
            <Switcher view={view} anchor={anchor} />
            {view === 'week' ? <WeekView days={days} anchor={anchor} today={today} /> : <MonthView days={days} anchor={anchor} month={month} today={today} />}

            {recurrences.length > 0 && (
                <section className="mt-6 rounded-2xl bg-white p-4 ring-1 ring-line">
                    <h2 className="mb-2 font-bold text-ink">Takrorlanadigan vazifalar</h2>
                    <ul className="divide-y divide-line text-sm">
                        {recurrences.map((r) => (
                            <li key={r.id} className="flex justify-between gap-3 py-2">
                                <span className="font-semibold text-ink">{r.title}</span>
                                <span className="shrink-0 text-muted">
                                    {r.rule}
                                    {r.time && `, ${r.time}`}
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </AppLayout>
    );
}
