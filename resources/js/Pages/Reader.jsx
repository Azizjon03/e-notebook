import { Head, Link, router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { prepareAlarm, ringAlarm } from '../lib/alarm';
import { sendJson } from '../lib/http';
import { t } from '../lib/i18n';
import { haptic } from '../telegram/webapp';

const pad = (n) => String(n).padStart(2, '0');

// pdf.js og'ir, shuning uchun faqat o'quvchi ochilganda yuklanadi.
// Worker Vite orqali .js fayl sifatida chiqadi (nginx .mjs ni noto'g'ri turda berishi mumkin).
async function loadPdfJs() {
    const [pdfjs, worker] = await Promise.all([import('pdfjs-dist/legacy/build/pdf.mjs'), import('pdfjs-dist/legacy/build/pdf.worker.min.mjs?worker')]);
    if (!pdfjs.GlobalWorkerOptions.workerPort) {
        pdfjs.GlobalWorkerOptions.workerPort = new worker.default();
    }
    return pdfjs;
}

function usePdf(url) {
    const [doc, setDoc] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
        let cancelled = false;
        let task = null;
        loadPdfJs()
            .then((pdfjs) => {
                task = pdfjs.getDocument({ url, withCredentials: true });
                return task.promise;
            })
            .then((d) => !cancelled && setDoc(d))
            .catch(() => !cancelled && setError(t("PDF ni ochib bo'lmadi.")));
        return () => {
            cancelled = true;
            task?.destroy();
        };
    }, [url]);

    return { doc, error };
}

function PageCanvas({ doc, page, zoom }) {
    const wrap = useRef(null);
    const canvas = useRef(null);
    const [busy, setBusy] = useState(true);

    useEffect(() => {
        if (!doc) return undefined;
        let task = null;
        let cancelled = false;
        setBusy(true);

        doc.getPage(page).then((p) => {
            if (cancelled) return;
            const base = p.getViewport({ scale: 1 });
            const width = Math.min(wrap.current.clientWidth, 900) * zoom;
            const viewport = p.getViewport({ scale: width / base.width });
            const dpr = Math.min(window.devicePixelRatio || 1, 3);
            const c = canvas.current;
            c.width = Math.floor(viewport.width * dpr);
            c.height = Math.floor(viewport.height * dpr);
            c.style.width = `${Math.floor(viewport.width)}px`;
            c.style.height = `${Math.floor(viewport.height)}px`;
            task = p.render({
                canvasContext: c.getContext('2d'),
                viewport,
                transform: dpr !== 1 ? [dpr, 0, 0, dpr, 0, 0] : null,
            });
            task.promise.then(() => !cancelled && setBusy(false)).catch(() => {});
        });

        return () => {
            cancelled = true;
            task?.cancel();
        };
    }, [doc, page, zoom]);

    return (
        <div ref={wrap} className="relative w-full overflow-x-auto">
            <canvas ref={canvas} className={`mx-auto block rounded-md bg-white shadow-sm ring-1 ring-line transition-opacity ${busy ? 'opacity-40' : ''}`} />
        </div>
    );
}

function useCountdown(running) {
    const [now, setNow] = useState(Date.now());
    useEffect(() => {
        if (!running) return undefined;
        const id = setInterval(() => setNow(Date.now()), 1000);
        return () => clearInterval(id);
    }, [running]);
    if (!running) return null;
    const elapsed = Math.max(0, Math.floor((now - new Date(running.started_at).getTime()) / 1000));
    return running.planned_minutes * 60 - elapsed;
}

function TimerBar({ book, running, page, onStop }) {
    const [minutes, setMinutes] = useState(25);
    const mine = running && running.book_id === book.id;
    const left = useCountdown(mine ? running : null);
    const [alarmed, setAlarmed] = useState(false);
    const [showDone, setShowDone] = useState(false);
    const pagesRead = mine ? Math.max(0, page - (running.start_page ?? page)) : 0;

    useEffect(() => {
        if (mine && left !== null && left <= 0 && !alarmed) {
            setAlarmed(true);
            // Sahifa ochilganda vaqt allaqachon o'tib ketgan bo'lsa, ovozsiz faqat oyna chiqadi.
            if (left > -5) ringAlarm(t('Vaqt tugadi!'), `${book.title}: ${t(":pages bet o'qildi", { pages: pagesRead })}`);
            setShowDone(true);
        }
    }, [mine, left, alarmed, book.title, pagesRead]);

    if (!mine) {
        return (
            <div className="flex flex-wrap items-center gap-2 text-sm">
                {running && <span className="w-full text-xs font-semibold text-gold">{t('Boshqa sessiya ketyapti, bu yerda boshlasangiz u yopiladi.')}</span>}
                <span className="font-semibold text-muted">{t('Taymer')}:</span>
                <div className="flex rounded-lg bg-bg p-1 font-bold">
                    {[15, 25, 50].map((m) => (
                        <button
                            key={m}
                            type="button"
                            onClick={() => setMinutes(m)}
                            className={`rounded-md px-2.5 py-1 ${minutes === m ? 'bg-card text-g800 shadow-sm' : 'text-muted'}`}
                        >
                            {t(':m daq', { m })}
                        </button>
                    ))}
                </div>
                <button
                    type="button"
                    onClick={() => {
                        prepareAlarm();
                        haptic('medium');
                        router.post(`/books/${book.id}/session`, { planned_minutes: minutes }, { preserveScroll: true, preserveState: true });
                    }}
                    className="ml-auto rounded-lg bg-g700 px-4 py-1.5 font-bold text-white"
                >
                    {t('Boshlash')}
                </button>
            </div>
        );
    }

    const shown = Math.abs(left ?? 0);
    const over = (left ?? 0) < 0;

    return (
        <>
            <div className="flex items-center gap-3 text-sm">
                <span className={`text-2xl font-extrabold tabular-nums ${over ? 'text-red' : 'text-g700'}`}>
                    {over && '+'}
                    {pad(Math.floor(shown / 60))}:{pad(shown % 60)}
                </span>
                <span className="font-semibold text-muted">{t(':pages bet', { pages: pagesRead })}</span>
                <button type="button" onClick={() => onStop(running.id)} className="ml-auto rounded-lg bg-g700 px-4 py-1.5 font-bold text-white">
                    {t('Tugatish')}
                </button>
            </div>

            {showDone &&
                createPortal(
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
                        <div className="w-full max-w-sm rounded-2xl bg-card p-6 text-center ring-1 ring-line">
                            <p className="text-4xl">⏰</p>
                            <h2 className="mt-2 text-xl font-extrabold text-ink">{t('Vaqt tugadi!')}</h2>
                            <p className="mt-2 text-muted">
                                {t(":minutes daqiqada :pages bet o'qidingiz.", {
                                    minutes: running.planned_minutes,
                                    pages: pagesRead,
                                })}
                            </p>
                            <div className="mt-5 flex gap-2">
                                <button type="button" onClick={() => setShowDone(false)} className="flex-1 rounded-xl py-2.5 font-bold text-muted ring-1 ring-line">
                                    {t('Davom etish')}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setShowDone(false);
                                        onStop(running.id);
                                    }}
                                    className="flex-1 rounded-xl bg-g700 py-2.5 font-bold text-white"
                                >
                                    {t('Tugatish')}
                                </button>
                            </div>
                        </div>
                    </div>,
                    document.body,
                )}
        </>
    );
}

export default function Reader({ book, pdfUrl, startPage, running }) {
    const { auth } = usePage().props;
    const demo = Boolean(auth?.user?.is_demo);
    const { doc, error } = usePdf(pdfUrl);
    const total = doc?.numPages ?? book.total_pages ?? null;
    const [page, setPage] = useState(startPage);
    const [zoom, setZoom] = useState(1);
    const [input, setInput] = useState(String(startPage));
    const [result, setResult] = useState(null);
    const saved = useRef(startPage);
    const touch = useRef(null);

    const go = useCallback(
        (p) => {
            const next = Math.max(1, total ? Math.min(total, p) : p);
            setPage(next);
            setInput(String(next));
            window.scrollTo({ top: 0 });
        },
        [total],
    );

    // Bet ochilgan bet sifatida saqlanadi (keyingi safar shu yerdan davom etadi).
    const save = useCallback(
        (opts) => {
            if (demo || saved.current === page) return Promise.resolve();
            saved.current = page;
            return sendJson('PUT', `/books/${book.id}/progress`, { page, total: doc?.numPages ?? null }, opts);
        },
        [demo, page, book.id, doc],
    );

    useEffect(() => {
        if (startPage > 1 && total && startPage > total) go(total);
    }, [total, startPage, go]);

    useEffect(() => {
        const id = setTimeout(() => save(), 1200);
        return () => clearTimeout(id);
    }, [save]);

    useEffect(() => {
        const flush = () => save({ keepalive: true });
        window.addEventListener('pagehide', flush);
        return () => window.removeEventListener('pagehide', flush);
    }, [save]);

    useEffect(() => {
        const onKey = (e) => {
            if (e.target.tagName === 'INPUT') return;
            if (e.key === 'ArrowRight' || e.key === 'PageDown') go(page + 1);
            if (e.key === 'ArrowLeft' || e.key === 'PageUp') go(page - 1);
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [page, go]);

    const stop = async (sessionId) => {
        const startedAt = running?.start_page ?? page;
        await save();
        setResult(Math.max(0, page - startedAt));
        router.post(`/study/${sessionId}/stop`, {}, { preserveScroll: true, preserveState: true });
    };

    return (
        <div className="min-h-screen bg-bg pb-28">
            <Head title={book.title} />
            <header className="sticky top-0 z-20 border-b border-line bg-card/95 px-4 py-2 backdrop-blur">
                <div className="mx-auto flex max-w-3xl items-center gap-3">
                    <Link href="/books" className="text-xl text-muted" aria-label={t('Orqaga')}>
                        ←
                    </Link>
                    <div className="min-w-0 flex-1">
                        <p className="truncate font-bold text-ink">{book.title}</p>
                        <p className="text-xs font-semibold text-muted">
                            {total
                                ? t(':current / :total bet', {
                                      current: page,
                                      total,
                                  })
                                : t(':pages bet', { pages: page })}
                        </p>
                    </div>
                    <div className="flex rounded-lg bg-bg p-1 text-sm font-bold">
                        <button type="button" onClick={() => setZoom((z) => Math.max(0.6, +(z - 0.2).toFixed(1)))} className="px-2 text-muted" aria-label={t('Kichraytirish')}>
                            −
                        </button>
                        <button type="button" onClick={() => setZoom((z) => Math.min(2.4, +(z + 0.2).toFixed(1)))} className="px-2 text-muted" aria-label={t('Kattalashtirish')}>
                            +
                        </button>
                    </div>
                </div>
                {!demo && (
                    <div className="mx-auto mt-2 max-w-3xl">
                        <TimerBar key={running?.id ?? 'none'} book={book} running={running} page={page} onStop={stop} />
                    </div>
                )}
            </header>

            {result !== null && !running && (
                <div className="mx-auto mt-3 max-w-3xl px-4">
                    <p className="rounded-xl bg-g700/10 px-4 py-2 text-sm font-semibold text-g700">
                        {t("Sessiya yakunlandi: :pages bet o'qildi.", {
                            pages: result,
                        })}
                    </p>
                </div>
            )}

            <main
                className="mx-auto mt-3 max-w-3xl px-2"
                onTouchStart={(e) => (touch.current = e.touches[0].clientX)}
                onTouchEnd={(e) => {
                    if (touch.current === null || zoom > 1) return;
                    const dx = e.changedTouches[0].clientX - touch.current;
                    touch.current = null;
                    if (Math.abs(dx) > 60) go(page + (dx < 0 ? 1 : -1));
                }}
            >
                {error && <p className="mt-10 text-center font-semibold text-red">{error}</p>}
                {!doc && !error && <p className="mt-10 text-center text-muted">{t('Yuklanmoqda...')}</p>}
                {doc && <PageCanvas doc={doc} page={page} zoom={zoom} />}
            </main>

            <nav className="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-card/95 px-4 py-3 backdrop-blur">
                <div className="mx-auto flex max-w-3xl items-center gap-3">
                    <button
                        type="button"
                        disabled={page <= 1}
                        onClick={() => go(page - 1)}
                        className="rounded-xl px-4 py-2 font-bold text-g700 ring-1 ring-line disabled:opacity-30"
                    >
                        ◀
                    </button>
                    <form
                        className="flex flex-1 items-center justify-center gap-2 text-sm font-semibold text-muted"
                        onSubmit={(e) => {
                            e.preventDefault();
                            const n = parseInt(input, 10);
                            if (n) go(n);
                        }}
                    >
                        <input
                            inputMode="numeric"
                            value={input}
                            onChange={(e) => setInput(e.target.value.replace(/\D/g, '').slice(0, 5))}
                            onBlur={() => setInput(String(page))}
                            className="w-16 rounded-lg border border-line bg-bg px-2 py-1.5 text-center text-ink"
                            aria-label={t('Bet')}
                        />
                        {total && <span>/ {total}</span>}
                    </form>
                    <button
                        type="button"
                        disabled={total && page >= total}
                        onClick={() => go(page + 1)}
                        className="rounded-xl bg-g700 px-4 py-2 font-bold text-white disabled:opacity-30"
                    >
                        ▶
                    </button>
                </div>
            </nav>
        </div>
    );
}
