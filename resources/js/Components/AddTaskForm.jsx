import { useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function AddTaskForm({ date }) {
    const [withTime, setWithTime] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        date,
        start_time: '',
        end_time: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/tasks', {
            preserveScroll: true,
            transform: (d) => ({ ...d, date, start_time: d.start_time || null, end_time: d.end_time || null }),
            onSuccess: () => reset('title', 'start_time', 'end_time'),
        });
    };

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

            {withTime ? (
                <div className="mt-3 flex items-center gap-2 text-sm">
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
            ) : (
                <button type="button" onClick={() => setWithTime(true)} className="mt-2 text-sm font-semibold text-g700">
                    + Vaqt qo'shish
                </button>
            )}

            {(errors.title || errors.end_time || errors.start_time) && (
                <p className="mt-2 text-sm font-semibold text-red">{errors.title || errors.end_time || errors.start_time}</p>
            )}
        </form>
    );
}
