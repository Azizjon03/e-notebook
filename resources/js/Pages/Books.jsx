import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../Components/AppLayout';
import { formatShort } from '../lib/dates';

const SECTIONS = [
    ['reading', "O'qilmoqda"],
    ['planned', 'Navbatda'],
    ['finished', 'Tugatilgan'],
];

function LogForm({ book, onDone }) {
    const form = useForm({ to_page: '', chapter: '' });
    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                form.post(`/books/${book.id}/log`, { preserveScroll: true, onSuccess: onDone });
            }}
            className="mt-3 space-y-2 border-t border-line pt-3 text-sm"
        >
            <div className="flex gap-2">
                <input
                    type="number"
                    inputMode="numeric"
                    autoFocus
                    min={book.current_page + 1}
                    max={book.total_pages ?? undefined}
                    value={form.data.to_page}
                    onChange={(e) => form.setData('to_page', e.target.value)}
                    placeholder={`Qaysi sahifagacha? (hozir ${book.current_page})`}
                    className="min-w-0 flex-1 rounded-lg border border-line px-2 py-2"
                />
                <button className="rounded-lg bg-g700 px-3 font-bold text-white" disabled={form.processing || !form.data.to_page}>
                    Saqlash
                </button>
            </div>
            <input
                value={form.data.chapter}
                onChange={(e) => form.setData('chapter', e.target.value)}
                placeholder="Bob (ixtiyoriy)"
                maxLength={150}
                className="w-full rounded-lg border border-line px-2 py-2"
            />
            {form.errors.to_page && <p className="font-semibold text-red">{form.errors.to_page}</p>}
        </form>
    );
}

function BookCard({ book, first, last }) {
    const [logging, setLogging] = useState(false);
    const opts = { preserveScroll: true };

    return (
        <li className="rounded-2xl bg-white p-4 ring-1 ring-line">
            <div className="flex gap-3">
                <div className={`w-1.5 shrink-0 rounded-full ${book.area === 'ilm' ? 'bg-gold' : 'bg-blue'}`} />
                <div className="min-w-0 flex-1">
                    <p className="font-bold text-ink">{book.title}</p>
                    <p className="text-sm text-muted">
                        {[book.author, book.stage].filter(Boolean).join(' · ') || (book.area === 'ilm' ? 'Ilm' : "O'qish")}
                    </p>
                    {book.total_pages && (
                        <div className="mt-2">
                            <div className="flex justify-between text-xs font-semibold text-muted">
                                <span>
                                    {book.current_page} / {book.total_pages} bet
                                    {book.last_chapter && ` · ${book.last_chapter}`}
                                </span>
                                <span>{book.percent}%</span>
                            </div>
                            <div className="mt-1 h-1.5 rounded-full bg-bg">
                                <div className="h-full rounded-full bg-g700" style={{ width: `${book.percent}%` }} />
                            </div>
                        </div>
                    )}
                    {book.status === 'finished' && book.finished_on && (
                        <p className="mt-1 text-xs font-semibold text-g700">{formatShort(book.finished_on)} da tugatildi</p>
                    )}
                </div>
                {book.status !== 'finished' && (
                    <div className="flex shrink-0 flex-col">
                        <button disabled={first} onClick={() => router.post(`/books/${book.id}/move`, { direction: 'up' }, opts)} className="px-1 text-muted disabled:opacity-20" aria-label="Yuqoriga">
                            ▲
                        </button>
                        <button disabled={last} onClick={() => router.post(`/books/${book.id}/move`, { direction: 'down' }, opts)} className="px-1 text-muted disabled:opacity-20" aria-label="Pastga">
                            ▼
                        </button>
                    </div>
                )}
            </div>

            {book.status !== 'finished' &&
                (logging ? (
                    <LogForm book={book} onDone={() => setLogging(false)} />
                ) : (
                    <div className="mt-3 flex gap-3 text-sm font-semibold">
                        <button onClick={() => setLogging(true)} className="text-g700">
                            + O'qiganimni yozish
                        </button>
                        <button
                            onClick={() => window.confirm("Kitobni o'chiraymi?") && router.delete(`/books/${book.id}`, opts)}
                            className="ml-auto text-red"
                        >
                            O'chirish
                        </button>
                    </div>
                ))}
        </li>
    );
}

function AddBook({ areas }) {
    const [open, setOpen] = useState(false);
    const form = useForm({ title: '', author: '', stage: '', total_pages: '', area: 'ilm' });

    if (!open) {
        return (
            <button onClick={() => setOpen(true)} className="w-full rounded-2xl border-2 border-dashed border-line py-4 font-bold text-g700">
                + Kitob qo'shish
            </button>
        );
    }

    const field = 'w-full rounded-lg border border-line px-3 py-2';
    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                form.transform((d) => ({ ...d, total_pages: d.total_pages || null }));
                form.post('/books', { preserveScroll: true, onSuccess: () => { form.reset(); setOpen(false); } });
            }}
            className="space-y-2 rounded-2xl bg-white p-4 text-sm ring-1 ring-line"
        >
            <input className={field} placeholder="Kitob nomi" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} maxLength={200} />
            <input className={field} placeholder="Muallif" value={form.data.author} onChange={(e) => form.setData('author', e.target.value)} maxLength={150} />
            <div className="flex gap-2">
                <input className={field} placeholder="Bosqich (ixtiyoriy)" value={form.data.stage} onChange={(e) => form.setData('stage', e.target.value)} maxLength={100} />
                <input className={`${field} w-28`} type="number" min="1" placeholder="Betlar" value={form.data.total_pages} onChange={(e) => form.setData('total_pages', e.target.value)} />
            </div>
            <select className={`${field} bg-white`} value={form.data.area} onChange={(e) => form.setData('area', e.target.value)}>
                {areas.map((a) => (
                    <option key={a.key} value={a.key}>
                        {a.name}
                    </option>
                ))}
            </select>
            {form.errors.title && <p className="font-semibold text-red">{form.errors.title}</p>}
            <div className="flex gap-2">
                <button type="button" onClick={() => setOpen(false)} className="flex-1 rounded-xl py-2.5 font-bold text-muted ring-1 ring-line">
                    Bekor qilish
                </button>
                <button className="flex-1 rounded-xl bg-g700 py-2.5 font-bold text-white" disabled={form.processing || !form.data.title.trim()}>
                    Qo'shish
                </button>
            </div>
        </form>
    );
}

export default function Books({ books, pagesWeek, areas }) {
    return (
        <AppLayout>
            <Head title="Kitoblar" />
            <div className="flex items-baseline justify-between">
                <h1 className="text-2xl font-extrabold text-ink">Kitoblar</h1>
                <span className="text-sm font-semibold text-muted">7 kunda {pagesWeek} bet</span>
            </div>

            {SECTIONS.map(([status, label]) => {
                const list = books.filter((b) => b.status === status);
                if (!list.length) return null;
                return (
                    <section key={status} className="mt-5">
                        <h2 className="mb-2 text-sm font-bold tracking-wide text-muted uppercase">{label}</h2>
                        <ul className="space-y-2">
                            {list.map((b, i) => (
                                <BookCard key={b.id} book={b} first={i === 0} last={i === list.length - 1} />
                            ))}
                        </ul>
                    </section>
                );
            })}

            {books.length === 0 && <p className="mt-6 text-center text-muted">Hali kitob qo'shilmagan. O'qiyotgan yoki o'qimoqchi bo'lgan kitoblaringizni qo'shing.</p>}

            <div className="mt-6">
                <AddBook areas={areas} />
            </div>
        </AppLayout>
    );
}
