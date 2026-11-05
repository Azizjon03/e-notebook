import { Head, Link, router, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import AppLayout from '../Components/AppLayout';
import { formatShort } from '../lib/dates';
import { t, tk } from '../lib/i18n';
import NumberInput from '../Components/NumberInput';

const SECTIONS = [
    ['reading', tk("O'qilmoqda")],
    ['planned', tk('Navbatda')],
    ['finished', tk('Tugatilgan')],
];

function LogForm({ book, onDone }) {
    const form = useForm({ to_page: '', chapter: '' });
    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                form.post(`/books/${book.id}/log`, {
                    preserveScroll: true,
                    onSuccess: onDone,
                });
            }}
            className="mt-3 space-y-2 border-t border-line pt-3 text-sm"
        >
            <div className="flex gap-2">
                <NumberInput
                    autoFocus
                    max={book.total_pages ?? undefined}
                    maxLength={5}
                    value={form.data.to_page}
                    onChange={(v) => form.setData('to_page', v)}
                    placeholder={`Qaysi sahifagacha? (hozir ${book.current_page})`}
                    className="min-w-0 flex-1 rounded-lg border border-line px-2 py-2"
                />
                <button className="rounded-lg bg-g700 px-3 font-bold text-white" disabled={form.processing || !form.data.to_page}>
                    {t('Saqlash')}
                </button>
            </div>
            <input
                value={form.data.chapter}
                onChange={(e) => form.setData('chapter', e.target.value)}
                placeholder={t('Bob (ixtiyoriy)')}
                maxLength={150}
                className="w-full rounded-lg border border-line px-2 py-2"
            />
            {form.errors.to_page && <p className="font-semibold text-red">{form.errors.to_page}</p>}
        </form>
    );
}

function PdfUpload({ book, maxMb, children, className }) {
    const input = useRef(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState(null);

    const upload = (file) => {
        if (!file) return;
        if (file.size > maxMb * 1024 * 1024) {
            setError(t("Fayl :mb MB dan katta bo'lmasin.", { mb: maxMb }));
            return;
        }
        setError(null);
        router.post(
            `/books/${book.id}/pdf`,
            { pdf: file },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setBusy(true),
                onFinish: () => setBusy(false),
                onError: (e) => setError(e.pdf ?? t("Yuklab bo'lmadi.")),
            },
        );
    };

    return (
        <>
            <input
                ref={input}
                type="file"
                accept="application/pdf,.pdf"
                className="hidden"
                onChange={(e) => {
                    upload(e.target.files?.[0]);
                    e.target.value = '';
                }}
            />
            <button type="button" disabled={busy} onClick={() => input.current?.click()} className={className}>
                {busy ? t('Yuklanmoqda...') : children}
            </button>
            {error && <p className="basis-full text-xs font-semibold text-red">{error}</p>}
        </>
    );
}

function BookCard({ book, first, last, maxMb }) {
    const [logging, setLogging] = useState(false);
    const opts = { preserveScroll: true };

    return (
        <li className="rounded-2xl bg-card p-4 ring-1 ring-line">
            <div className="flex gap-3">
                <div className={`w-1.5 shrink-0 rounded-full ${book.area === 'ilm' ? 'bg-gold' : 'bg-blue'}`} />
                <div className="min-w-0 flex-1">
                    <p className="font-bold text-ink">{book.title}</p>
                    <p className="text-sm text-muted">{[book.author, book.stage].filter(Boolean).join(' · ') || (book.area === 'ilm' ? t('Ilm') : t("O'qish"))}</p>
                    {book.total_pages && (
                        <div className="mt-2">
                            <div className="flex justify-between text-xs font-semibold text-muted">
                                <span>
                                    {t(':current / :total bet', {
                                        current: book.current_page,
                                        total: book.total_pages,
                                    })}
                                    {book.last_chapter && ` · ${book.last_chapter}`}
                                </span>
                                <span>{book.percent}%</span>
                            </div>
                            <div className="mt-1 h-1.5 rounded-full bg-bg">
                                <div className="h-full rounded-full bg-g700" style={{ width: `${book.percent}%` }} />
                            </div>
                        </div>
                    )}
                    {book.pages_today > 0 && (
                        <p className="mt-1 text-xs font-semibold text-g700">
                            {t("Bugun :pages bet o'qildi", {
                                pages: book.pages_today,
                            })}
                        </p>
                    )}
                    {book.status === 'finished' && book.finished_on && <p className="mt-1 text-xs font-semibold text-g700">{formatShort(book.finished_on)} da tugatildi</p>}
                </div>
                {book.status !== 'finished' && (
                    <div className="flex shrink-0 flex-col">
                        <button
                            disabled={first}
                            onClick={() => router.post(`/books/${book.id}/move`, { direction: 'up' }, opts)}
                            className="px-1 text-muted disabled:opacity-20"
                            aria-label={t('Yuqoriga')}
                        >
                            ▲
                        </button>
                        <button
                            disabled={last}
                            onClick={() => router.post(`/books/${book.id}/move`, { direction: 'down' }, opts)}
                            className="px-1 text-muted disabled:opacity-20"
                            aria-label={t('Pastga')}
                        >
                            ▼
                        </button>
                    </div>
                )}
            </div>

            <div className="mt-3 flex flex-wrap items-center gap-2 text-sm font-semibold">
                {book.has_pdf ? (
                    <>
                        <Link href={`/books/${book.id}/read`} className="rounded-lg bg-g700 px-3 py-1.5 font-bold text-white">
                            {t("📖 O'qish")}
                        </Link>
                        <span className="min-w-0 flex-1 truncate text-xs text-muted" title={book.pdf_name}>
                            PDF · {book.pdf_size >= 1048576 ? `${(book.pdf_size / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(book.pdf_size / 1024))} KB`}
                        </span>
                        <PdfUpload book={book} maxMb={maxMb} className="text-xs text-muted">
                            {t('Almashtirish')}
                        </PdfUpload>
                        <button
                            type="button"
                            onClick={() => window.confirm(t("PDF faylni o'chiraymi?")) && router.delete(`/books/${book.id}/pdf`, opts)}
                            className="text-xs text-red"
                        >
                            {t("PDF ni o'chirish")}
                        </button>
                    </>
                ) : (
                    <PdfUpload book={book} maxMb={maxMb} className="text-g700">
                        {t('+ PDF yuklash')}
                    </PdfUpload>
                )}
            </div>

            {book.status !== 'finished' &&
                (logging ? (
                    <LogForm book={book} onDone={() => setLogging(false)} />
                ) : (
                    <div className="mt-3 flex gap-3 text-sm font-semibold">
                        <button onClick={() => setLogging(true)} className="text-g700">
                            {t("+ O'qiganimni yozish")}
                        </button>
                        <button onClick={() => window.confirm(t("Kitobni o'chiraymi?")) && router.delete(`/books/${book.id}`, opts)} className="ml-auto text-red">
                            {t("O'chirish")}
                        </button>
                    </div>
                ))}
        </li>
    );
}

function AddBook({ areas }) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        title: '',
        author: '',
        stage: '',
        total_pages: '',
        area: 'ilm',
    });

    if (!open) {
        return (
            <button onClick={() => setOpen(true)} className="w-full rounded-2xl border-2 border-dashed border-line py-4 font-bold text-g700">
                {t("+ Kitob qo'shish")}
            </button>
        );
    }

    const field = 'w-full rounded-lg border border-line px-3 py-2';
    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                form.transform((d) => ({
                    ...d,
                    total_pages: d.total_pages || null,
                }));
                form.post('/books', {
                    preserveScroll: true,
                    onSuccess: () => {
                        form.reset();
                        setOpen(false);
                    },
                });
            }}
            className="space-y-2 rounded-2xl bg-card p-4 text-sm ring-1 ring-line"
        >
            <input className={field} placeholder={t('Kitob nomi')} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} maxLength={200} />
            <input className={field} placeholder={t('Muallif')} value={form.data.author} onChange={(e) => form.setData('author', e.target.value)} maxLength={150} />
            <div className="flex gap-2">
                <input className={field} placeholder={t('Bosqich (ixtiyoriy)')} value={form.data.stage} onChange={(e) => form.setData('stage', e.target.value)} maxLength={100} />
                <NumberInput className={`${field} w-28`} maxLength={5} placeholder={t('Betlar')} value={form.data.total_pages} onChange={(v) => form.setData('total_pages', v)} />
            </div>
            <select className={`${field} bg-card`} value={form.data.area} onChange={(e) => form.setData('area', e.target.value)}>
                {areas.map((a) => (
                    <option key={a.key} value={a.key}>
                        {a.name}
                    </option>
                ))}
            </select>
            {form.errors.title && <p className="font-semibold text-red">{form.errors.title}</p>}
            <div className="flex gap-2">
                <button type="button" onClick={() => setOpen(false)} className="flex-1 rounded-xl py-2.5 font-bold text-muted ring-1 ring-line">
                    {t('Bekor qilish')}
                </button>
                <button className="flex-1 rounded-xl bg-g700 py-2.5 font-bold text-white" disabled={form.processing || !form.data.title.trim()}>
                    {t("Qo'shish")}
                </button>
            </div>
        </form>
    );
}

export default function Books({ books, pagesWeek, pagesToday, maxPdfMb, areas }) {
    return (
        <AppLayout>
            <Head title={t('Kitoblar')} />
            <div className="flex items-baseline justify-between">
                <h1 className="text-2xl font-extrabold text-ink">{t('Kitoblar')}</h1>
                <span className="text-sm font-semibold text-muted">
                    {t('Bugun :pages bet', { pages: pagesToday })} · {t('7 kunda :pages bet', { pages: pagesWeek })}
                </span>
            </div>

            {SECTIONS.map(([status, label]) => {
                const list = books.filter((b) => b.status === status);
                if (!list.length) return null;
                return (
                    <section key={status} className="mt-5">
                        <h2 className="mb-2 text-sm font-bold tracking-wide text-muted uppercase">{t(label)}</h2>
                        <ul className="space-y-2">
                            {list.map((b, i) => (
                                <BookCard key={b.id} book={b} maxMb={maxPdfMb} first={i === 0} last={i === list.length - 1} />
                            ))}
                        </ul>
                    </section>
                );
            })}

            {books.length === 0 && <p className="mt-6 text-center text-muted">{t("Hali kitob qo'shilmagan. O'qiyotgan yoki o'qimoqchi bo'lgan kitoblaringizni qo'shing.")}</p>}

            <div className="mt-6">
                <AddBook areas={areas} />
            </div>
        </AppLayout>
    );
}
