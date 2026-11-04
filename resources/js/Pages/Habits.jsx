import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../Components/AppLayout';

const DAYS = [
    [1, 'Du'],
    [2, 'Se'],
    [3, 'Cho'],
    [4, 'Pa'],
    [5, 'Ju'],
    [6, 'Sha'],
    [7, 'Ya'],
];
const KINDS = { check: 'Belgilash', count: 'Son (masalan qadam)', duration: 'Daqiqa' };

function patch(habit, data) {
    router.patch(`/habits/${habit.id}`, data, { preserveScroll: true });
}

function DayPicker({ value, onChange }) {
    const days = value ?? [1, 2, 3, 4, 5, 6, 7];
    const toggle = (d) => onChange(days.includes(d) ? days.filter((x) => x !== d) : [...days, d]);
    return (
        <div className="flex flex-wrap gap-1">
            {DAYS.map(([d, label]) => (
                <button
                    key={d}
                    type="button"
                    onClick={() => toggle(d)}
                    className={`h-8 w-9 rounded-lg text-xs font-bold ${days.includes(d) ? 'bg-g700 text-white' : 'bg-bg text-muted ring-1 ring-line'}`}
                >
                    {label}
                </button>
            ))}
        </div>
    );
}

function HabitRow({ habit }) {
    return (
        <li className={`rounded-2xl bg-white p-4 ring-1 ring-line ${habit.is_active ? '' : 'opacity-60'}`}>
            <div className="flex items-center gap-3">
                <div className="min-w-0 flex-1">
                    <p className="font-bold text-ink">{habit.name}</p>
                    <div className="mt-1 flex gap-1" aria-label="Oxirgi 7 kun">
                        {habit.week.map((done, i) => (
                            <span key={i} className={`h-2 w-5 rounded-full ${done ? 'bg-g700' : 'bg-line'}`} />
                        ))}
                    </div>
                </div>
                <button
                    type="button"
                    role="switch"
                    aria-checked={habit.is_active}
                    onClick={() => patch(habit, { is_active: !habit.is_active })}
                    className={`relative h-7 w-12 rounded-full transition ${habit.is_active ? 'bg-g700' : 'bg-line'}`}
                >
                    <span className={`absolute top-1 h-5 w-5 rounded-full bg-white transition-all ${habit.is_active ? 'left-6' : 'left-1'}`} />
                </button>
            </div>

            {habit.is_active && !['fajr', 'dhuhr', 'asr', 'maghrib', 'isha'].includes(habit.key) && (
                <div className="mt-3 space-y-2 border-t border-line pt-3 text-sm">
                    {habit.kind !== 'check' && (
                        <label className="flex items-center gap-2">
                            <span className="w-16 text-muted">Maqsad</span>
                            <input
                                type="number"
                                min="1"
                                defaultValue={habit.target}
                                onBlur={(e) => Number(e.target.value) !== habit.target && patch(habit, { target: Number(e.target.value) })}
                                className="w-28 rounded-lg border border-line px-2 py-1"
                            />
                            {habit.kind === 'duration' && <span className="text-muted">daqiqa</span>}
                        </label>
                    )}
                    <div className="flex items-start gap-2">
                        <span className="w-16 pt-1.5 text-muted">Kunlar</span>
                        <DayPicker value={habit.weekdays} onChange={(days) => patch(habit, { weekdays: days })} />
                    </div>
                    {habit.key === 'gym' && (
                        <Link href="/gym" className="inline-block font-semibold text-g700">
                            Zal dasturi va mashg'ulotlar →
                        </Link>
                    )}
                    {habit.custom && (
                        <button
                            type="button"
                            onClick={() => router.delete(`/habits/${habit.id}`, { preserveScroll: true })}
                            className="text-xs font-semibold text-red"
                        >
                            Odatni o'chirish
                        </button>
                    )}
                </div>
            )}
        </li>
    );
}

function AddHabit({ areas }) {
    const form = useForm({ name: '', area: 'routine', kind: 'check', target: '', weekdays: null });

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                form.post('/habits', { preserveScroll: true, onSuccess: () => form.reset() });
            }}
            className="space-y-3 rounded-2xl bg-white p-4 text-sm ring-1 ring-line"
        >
            <h2 className="text-base font-bold text-ink">Yangi odat</h2>
            <input
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
                placeholder="Masalan: Qur'on o'qish"
                maxLength={100}
                className="w-full rounded-xl border border-line bg-bg px-3 py-2.5 text-base"
            />
            <div className="flex gap-2">
                <select value={form.data.area} onChange={(e) => form.setData('area', e.target.value)} className="flex-1 rounded-lg border border-line bg-white px-2 py-2">
                    {areas.map((a) => (
                        <option key={a.key} value={a.key}>
                            {a.name}
                        </option>
                    ))}
                </select>
                <select value={form.data.kind} onChange={(e) => form.setData('kind', e.target.value)} className="flex-1 rounded-lg border border-line bg-white px-2 py-2">
                    {Object.entries(KINDS).map(([k, label]) => (
                        <option key={k} value={k}>
                            {label}
                        </option>
                    ))}
                </select>
            </div>
            {form.data.kind !== 'check' && (
                <input
                    type="number"
                    min="1"
                    value={form.data.target}
                    onChange={(e) => form.setData('target', e.target.value)}
                    placeholder={form.data.kind === 'duration' ? 'Maqsad, daqiqa' : 'Maqsad, soni'}
                    className="w-full rounded-lg border border-line px-2 py-2"
                />
            )}
            <DayPicker value={form.data.weekdays} onChange={(days) => form.setData('weekdays', days)} />
            {(form.errors.name || form.errors.target) && <p className="font-semibold text-red">{form.errors.name || form.errors.target}</p>}
            <button className="w-full rounded-xl bg-g700 py-2.5 font-bold text-white disabled:opacity-40" disabled={form.processing || !form.data.name.trim()}>
                Qo'shish
            </button>
        </form>
    );
}

export default function Habits({ habits, areas }) {
    const groups = areas.map((a) => ({ ...a, habits: habits.filter((h) => h.area === a.key) })).filter((g) => g.habits.length);

    return (
        <AppLayout>
            <Head title="Odatlar" />
            <h1 className="text-2xl font-extrabold text-ink">Odatlar</h1>
            <p className="mt-1 text-sm text-muted">Yoqilgan odatlar Bugun sahifasida chiqadi. Chiziqchalar oxirgi 7 kunni ko'rsatadi.</p>

            {groups.map((g) => (
                <section key={g.key} className="mt-5">
                    <h2 className="mb-2 text-sm font-bold tracking-wide text-muted uppercase">{g.name}</h2>
                    <ul className="space-y-2">
                        {g.habits.map((h) => (
                            <HabitRow key={h.id} habit={h} />
                        ))}
                    </ul>
                </section>
            ))}

            <div className="mt-6">
                <AddHabit areas={areas} />
            </div>
        </AppLayout>
    );
}
