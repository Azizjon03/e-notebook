import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AppLayout from '../Components/AppLayout';
import { haptic } from '../telegram/webapp';
import { formatShort } from '../lib/dates';
import { t, tk } from '../lib/i18n';

const WEEK = { 1: tk('Du'), 2: tk('Se'), 3: tk('Cho'), 4: tk('Pa'), 5: tk('Ju'), 6: tk('Sha'), 7: tk('Ya') };
const pad = (n) => String(n).padStart(2, '0');

function GenderPicker() {
    const pick = (gender) => router.post('/gym/gender', { gender }, { preserveScroll: true });
    return (
        <section className="rounded-2xl bg-white p-5 ring-1 ring-line">
            <h2 className="font-bold text-ink">{t("Dasturni tanlashdan oldin")}</h2>
            <p className="mt-1 text-sm text-muted">{t("Sizga mos dasturlarni ko'rsatishimiz uchun jinsingizni tanlang.")}</p>
            <div className="mt-4 grid grid-cols-2 gap-3">
                <button type="button" onClick={() => pick('male')} className="rounded-xl bg-g100 py-3 font-bold text-g800">
                    {t("Erkak")}
                </button>
                <button type="button" onClick={() => pick('female')} className="rounded-xl bg-g100 py-3 font-bold text-g800">
                    {t("Ayol")}
                </button>
            </div>
        </section>
    );
}

function RestTimer({ seconds, onDone }) {
    const [left, setLeft] = useState(seconds);
    useEffect(() => {
        if (left <= 0) {
            haptic('heavy');
            onDone();
            return undefined;
        }
        const id = setTimeout(() => setLeft((s) => s - 1), 1000);
        return () => clearTimeout(id);
    }, [left, onDone]);

    return (
        <div className="fixed inset-x-0 bottom-20 z-20 mx-auto flex max-w-md items-center justify-between rounded-2xl bg-ink px-5 py-3 text-white shadow-lg">
            <span className="text-sm font-semibold">{t("Dam olish")}</span>
            <span className="text-2xl font-extrabold tabular-nums">
                {pad(Math.floor(left / 60))}:{pad(left % 60)}
            </span>
            <button type="button" onClick={onDone} className="text-sm font-bold text-g500">
                {t("O'tkazib yuborish")}
            </button>
        </div>
    );
}

function Exercise({ exercise, index, done, disabled, onSet }) {
    return (
        <li className="rounded-xl bg-bg p-3">
            <div className="flex items-baseline justify-between gap-2">
                <p className="text-sm font-bold text-ink">{exercise.name}</p>
                <span className="shrink-0 text-xs font-semibold text-muted">
                    {exercise.sets} × {exercise.reps}
                </span>
            </div>
            <div className="mt-2 flex flex-wrap gap-2">
                {Array.from({ length: exercise.sets }, (_, i) => {
                    const checked = i < done;
                    return (
                        <button
                            key={i}
                            type="button"
                            disabled={disabled}
                            aria-pressed={checked}
                            onClick={() => onSet(index, checked ? i : i + 1, !checked && exercise.rest)}
                            className={`h-9 min-w-9 rounded-lg px-2 text-sm font-bold ${
                                checked ? 'bg-g700 text-white' : 'bg-white text-muted ring-1 ring-line'
                            }`}
                        >
                            {checked ? '✓' : i + 1}
                        </button>
                    );
                })}
            </div>
        </li>
    );
}

function Workout({ workout, program }) {
    const [rest, setRest] = useState(null);
    const exercises = workout.day.exercises;
    const doneSets = exercises.reduce((sum, _, i) => sum + (workout.completed[i] ?? 0), 0);
    const totalSets = exercises.reduce((sum, e) => sum + e.sets, 0);

    const onSet = (exercise, done, restSeconds) => {
        haptic(done ? 'medium' : 'light');
        router.post('/gym/set', { exercise, done }, { preserveScroll: true, preserveState: true });
        if (restSeconds) setRest({ seconds: restSeconds, key: Date.now() });
    };

    return (
        <section className="rounded-2xl bg-white p-4 ring-1 ring-line">
            <div className="flex items-baseline justify-between">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted">{program.name}</p>
                    <h2 className="text-lg font-extrabold text-ink">
                        {t('Bugun: :day', { day: workout.day.name })}
                    </h2>
                </div>
                <span className="text-sm font-bold text-g700">
                    {t(':done/:total set', { done: doneSets, total: totalSets })}
                </span>
            </div>
            {!workout.scheduled_today && !workout.finished && (
                <p className="mt-2 rounded-lg bg-g100 px-3 py-2 text-xs text-g800">{t("Bugun reja bo'yicha dam olish kuni, lekin xohlasangiz shug'ullanishingiz mumkin.")}</p>
            )}

            <ul className="mt-3 space-y-2">
                {exercises.map((e, i) => (
                    <Exercise key={i} exercise={e} index={i} done={workout.completed[i] ?? 0} disabled={workout.finished} onSet={onSet} />
                ))}
            </ul>

            {workout.finished ? (
                <p className="mt-4 rounded-xl bg-g100 py-3 text-center font-bold text-g800">{t("Bugungi mashg'ulot tugadi. Barakalla!")}</p>
            ) : (
                <button
                    type="button"
                    onClick={() => {
                        haptic('heavy');
                        router.post('/gym/finish', {}, { preserveScroll: true });
                    }}
                    className="mt-4 w-full rounded-xl bg-g700 py-3 font-bold text-white"
                >
                    {t("Mashg'ulotni tugatish")}
                </button>
            )}

            {rest && <RestTimer key={rest.key} seconds={rest.seconds} onDone={() => setRest(null)} />}
        </section>
    );
}

function ProgramCard({ program, current }) {
    const [open, setOpen] = useState(false);
    const choose = () => {
        haptic('medium');
        router.post('/gym/program', { program: program.key }, { preserveScroll: true });
    };

    return (
        <li className={`rounded-2xl bg-white p-4 ring-1 ${current ? 'ring-2 ring-g700' : 'ring-line'}`}>
            <div className="flex items-start justify-between gap-2">
                <div>
                    <p className="font-bold text-ink">{program.name}</p>
                    <p className="text-xs text-muted">
                        {program.level} · {program.weekdays.map((d) => t(WEEK[d])).join(', ')}
                    </p>
                </div>
                {program.recommended && <span className="rounded-full bg-gold/15 px-2 py-0.5 text-xs font-bold text-gold">{t("Tavsiya")}</span>}
            </div>
            <p className="mt-2 text-sm text-ink">{program.about}</p>

            {open && (
                <div className="mt-3 space-y-3 border-t border-line pt-3">
                    {program.days.map((d) => (
                        <div key={d.name}>
                            <p className="text-sm font-bold text-ink">{d.name}</p>
                            <ul className="mt-1 space-y-0.5 text-sm text-muted">
                                {d.exercises.map((e) => (
                                    <li key={e.name}>
                                        {e.name} — {e.sets} × {e.reps}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
            )}

            <div className="mt-3 flex gap-2">
                <button type="button" onClick={() => setOpen(!open)} className="flex-1 rounded-xl bg-bg py-2 text-sm font-semibold text-ink">
                    {open ? t('Yopish') : t("Mashqlarni ko'rish")}
                </button>
                {current ? (
                    <span className="flex-1 rounded-xl bg-g100 py-2 text-center text-sm font-bold text-g800">{t("Tanlangan")}</span>
                ) : (
                    <button type="button" onClick={choose} className="flex-1 rounded-xl bg-g700 py-2 text-sm font-bold text-white">
                        {t("Shu dasturni tanlash")}
                    </button>
                )}
            </div>
        </li>
    );
}

export default function Gym({ gender, programs, current, workout, history }) {
    const program = programs.find((p) => p.key === current);

    return (
        <AppLayout>
            <Head title={t("Zal")} />
            <h1 className="mb-4 text-xl font-extrabold text-ink">{t("Zal")}</h1>

            {!gender && <GenderPicker />}

            {workout && program && <Workout workout={workout} program={program} />}

            {gender && (
                <>
                    <h2 className="mb-2 mt-6 font-bold text-ink">{current ? t('Boshqa dasturlar') : t('Dastur tanlang')}</h2>
                    <ul className="space-y-3">
                        {programs.map((p) => (
                            <ProgramCard key={p.key} program={p} current={p.key === current} />
                        ))}
                    </ul>
                </>
            )}

            {history.length > 0 && (
                <section className="mt-6 rounded-2xl bg-white p-4 ring-1 ring-line">
                    <h2 className="mb-2 font-bold text-ink">{t("Oxirgi mashg'ulotlar")}</h2>
                    <ul className="divide-y divide-line text-sm">
                        {history.map((h) => (
                            <li key={h.date} className="flex justify-between py-2">
                                <span className="text-muted">{formatShort(h.date)}</span>
                                <span className="font-semibold text-ink">{h.day}</span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </AppLayout>
    );
}
