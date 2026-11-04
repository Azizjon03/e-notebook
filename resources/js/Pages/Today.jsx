import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../Components/AppLayout';
import AddTaskForm from '../Components/AddTaskForm';
import HabitList from '../Components/HabitList';
import PrayerTimes from '../Components/PrayerTimes';
import TaskItem from '../Components/TaskItem';
import { addDays, formatLong } from '../lib/dates';

export default function Today({ date, today, tasks, stats, overdue, prayers, habits, now, level }) {
    const isToday = date === today;
    const canMark = date <= today;
    const percent = stats.total ? Math.round((stats.done / stats.total) * 100) : 0;
    const title = isToday ? 'Bugun' : date === addDays(today, 1) ? 'Ertaga' : date === addDays(today, -1) ? 'Kecha' : formatLong(date);

    return (
        <AppLayout>
            <Head title={title} />

            <section className="rounded-2xl bg-g700 p-5 text-white">
                <div className="flex items-center justify-between">
                    <Link href={`/?date=${addDays(date, -1)}`} preserveScroll className="rounded-lg px-2 py-1 text-lg" aria-label="Oldingi kun">
                        ‹
                    </Link>
                    <div className="text-center">
                        <h1 className="text-2xl font-extrabold">{title}</h1>
                        <p className="text-sm text-white/80">{formatLong(date)}</p>
                        {level && (
                            <Link href="/study" className="mt-1 inline-block rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-bold">
                                {level.level}-daraja · {level.total} ball
                            </Link>
                        )}
                    </div>
                    <Link href={`/?date=${addDays(date, 1)}`} preserveScroll className="rounded-lg px-2 py-1 text-lg" aria-label="Keyingi kun">
                        ›
                    </Link>
                </div>

                <div className="mt-4">
                    <div className="flex justify-between text-sm font-semibold">
                        <span>
                            {stats.done} / {stats.total} bajarildi
                        </span>
                        <span>{percent}%</span>
                    </div>
                    <div className="mt-2 h-2 overflow-hidden rounded-full bg-white/25">
                        <div className="h-full rounded-full bg-white transition-all" style={{ width: `${percent}%` }} />
                    </div>
                </div>
            </section>

            {!isToday && (
                <Link href="/" className="mt-3 block text-center text-sm font-semibold text-g700">
                    Bugunga qaytish
                </Link>
            )}

            <PrayerTimes prayers={prayers} habits={habits} date={date} now={now} isToday={isToday} canMark={canMark} />

            {overdue > 0 && (
                <div className="mt-4 flex items-center justify-between gap-3 rounded-2xl bg-white p-4 ring-1 ring-gold/30">
                    <p className="text-sm text-ink">
                        O'tgan kunlardan <b>{overdue} ta</b> bajarilmagan vazifa qoldi.
                    </p>
                    <button
                        type="button"
                        onClick={() => router.post('/tasks/carry-over', {}, { preserveScroll: true })}
                        className="shrink-0 rounded-xl bg-gold px-3 py-2 text-sm font-bold text-white"
                    >
                        Bugunga olish
                    </button>
                </div>
            )}

            <div className="mt-4">
                <AddTaskForm date={date} key={date} />
            </div>

            {tasks.length ? (
                <ul className="mt-4 space-y-2">
                    {tasks.map((task) => (
                        <TaskItem key={task.id} task={task} />
                    ))}
                </ul>
            ) : (
                <p className="mt-8 text-center text-muted">Bu kunga hali vazifa yo'q.</p>
            )}

            <HabitList habits={habits} date={date} canMark={canMark} />
        </AppLayout>
    );
}
