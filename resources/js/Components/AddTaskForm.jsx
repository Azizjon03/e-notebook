import { useForm } from '@inertiajs/react';
import { useState } from 'react';

const WEEKDAYS = [
    [1, 'Du'],
    [2, 'Se'],
    [3, 'Cho'],
    [4, 'Pa'],
    [5, 'Ju'],
    [6, 'Sha'],
    [7, 'Ya'],
];

const REPEAT = [
    ['', 'Takrorlanmaydi'],
    ['daily', 'Har kuni'],
    ['weekly', 'Har hafta'],
    ['monthly', 'Har oy'],
];

export default function AddTaskForm({ date }) {
    const [more, setMore] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        date,
        start_time: '',
        end_time: '',
        repeat: '',
        weekdays: [],
        until: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/tasks', {
            preserveScroll: true,
            transform: (d) => ({
                ...d,
                date,
                start_time: d.start_time || null,
                end_time: d.end_time || null,
                repeat: d.repeat || null,
                until: d.until || null,
            }),
            onSuccess: () => reset(),
        });
    };

    const toggleDay = (day) =>
        setData('weekdays', data.weekdays.includes(day) ? data.weekdays.filter((d) => d !== day) : [...data.weekdays, day]);

    const error = errors.title || errors.end_time || errors.start_time || errors.weekdays || errors.until;

    return (
        <form onSubmit={submit} className="rounded-2xl bg-white p-4 ring-1 ring-line">
            <div className="flex gap-2">
                <input
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    placeholder="Yangi vazifa..."
                    maxLength={200}
                    className="min-w-0 flex-1 rounded-xl border border-line bg-bg px-3 py-2.5 text-ink outline-none focus:border-g500"
                />
                <button
                    type="submit"
                    disabled={processing || !data.title.trim()}
                    className="rounded-xl bg-g700 px-4 font-bold text-white disabled:opacity-40"
                >
                    Qo'shish
                </button>
            </div>

            {more ? (
                <div className="mt-3 space-y-3 text-sm">
                    <div className="flex items-center gap-2">
                        <span className="w-20 text-muted">Vaqt</span>
                        <input
                            type="time"
                            value={data.start_time}
                            onChange={(e) => setData('start_time', e.target.value)}
                            className="rounded-lg border border-line px-2 py-1.5"
                        />
                        <span className="text-muted">–</span>
                        <input
                            type="time"
                            value={data.end_time}
                            onChange={(e) => setData('end_time', e.target.value)}
                            className="rounded-lg border border-line px-2 py-1.5"
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="w-20 text-muted">Takror</span>
                        <select
                            value={data.repeat}
                            onChange={(e) => setData('repeat', e.target.value)}
                            className="rounded-lg border border-line bg-white px-2 py-1.5"
                        >
                            {REPEAT.map(([value, label]) => (
                                <option key={value} value={value}>
                                    {label}
                                </option>
                            ))}
                        </select>
                    </div>
                    {data.repeat === 'weekly' && (
                        <div className="flex flex-wrap gap-1.5 pl-[5.5rem]">
                            {WEEKDAYS.map(([day, label]) => (
                                <button
                                    type="button"
                                    key={day}
                                    onClick={() => toggleDay(day)}
                                    className={`h-9 w-11 rounded-lg text-xs font-bold ${
                                        data.weekdays.includes(day) ? 'bg-g700 text-white' : 'bg-bg text-muted ring-1 ring-line'
                                    }`}
                                >
                                    {label}
                                </button>
                            ))}
                        </div>
                    )}
                    {data.repeat && (
                        <div className="flex items-center gap-2">
                            <span className="w-20 text-muted">Tugashi</span>
                            <input
                                type="date"
                                value={data.until}
                                min={date}
                                onChange={(e) => setData('until', e.target.value)}
                                className="rounded-lg border border-line px-2 py-1.5"
                            />
                            <span className="text-xs text-muted">bo'sh = muddatsiz</span>
                        </div>
                    )}
                </div>
            ) : (
                <button type="button" onClick={() => setMore(true)} className="mt-2 text-sm font-semibold text-g700">
                    + Vaqt va takrorlash
                </button>
            )}

            {error && <p className="mt-2 text-sm font-semibold text-red">{error}</p>}
        </form>
    );
}
