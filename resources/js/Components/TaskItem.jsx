import { router } from '@inertiajs/react';
import { haptic } from '../telegram/webapp';
import { formatShort } from '../lib/dates';

export default function TaskItem({ task }) {
    const opts = { preserveScroll: true };

    const toggle = () => {
        haptic(task.done ? 'light' : 'medium');
        router.post(`/tasks/${task.id}/toggle`, {}, opts);
    };

    return (
        <li className="flex items-start gap-3 rounded-2xl bg-white p-4 ring-1 ring-line">
            <button
                type="button"
                onClick={toggle}
                aria-label={task.done ? 'Bajarilmagan deb belgilash' : 'Bajarildi deb belgilash'}
                className={`mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full border-2 transition ${
                    task.done ? 'border-g700 bg-g700 text-white' : 'border-line bg-white hover:border-g500'
                }`}
            >
                {task.done && (
                    <svg viewBox="0 0 16 16" className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="2.5">
                        <path d="M3.5 8.5l3 3 6-7" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                )}
            </button>

            <div className="min-w-0 flex-1">
                <p className={`font-semibold ${task.done ? 'text-muted line-through' : 'text-ink'}`}>{task.title}</p>
                <div className="mt-1 flex flex-wrap gap-2 text-xs font-medium text-muted">
                    {task.start_time && (
                        <span className="rounded-full bg-g100 px-2 py-0.5 text-g800">
                            {task.start_time}
                            {task.end_time && `–${task.end_time}`}
                        </span>
                    )}
                    {task.moved_from && <span>{formatShort(task.moved_from)} dan ko'chirildi</span>}
                </div>
            </div>

            <div className="flex shrink-0 gap-1">
                {!task.done && (
                    <button
                        type="button"
                        onClick={() => router.post(`/tasks/${task.id}/move-tomorrow`, {}, opts)}
                        className="rounded-lg px-2 py-1 text-xs font-semibold text-blue hover:bg-bg"
                    >
                        Ertaga
                    </button>
                )}
                <button
                    type="button"
                    onClick={() => router.delete(`/tasks/${task.id}`, opts)}
                    aria-label="O'chirish"
                    className="rounded-lg px-2 py-1 text-xs font-semibold text-red hover:bg-bg"
                >
                    O'chirish
                </button>
            </div>
        </li>
    );
}
