import { Head, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../Components/AppLayout';
import LanguageSwitcher from '../Components/LanguageSwitcher';
import { t, tk } from '../lib/i18n';

const DAYS = [
    [1, tk('Dushanba')],
    [2, tk('Seshanba')],
    [3, tk('Chorshanba')],
    [4, tk('Payshanba')],
    [5, tk('Juma')],
    [6, tk('Shanba')],
    [7, tk('Yakshanba')],
];

const box = 'rounded-2xl bg-white p-4 ring-1 ring-line space-y-3';
const select = 'rounded-lg border border-line bg-white px-2 py-2 text-sm';

function Row({ label, hint, children }) {
    return (
        <div className="flex items-center justify-between gap-3">
            <div className="min-w-0">
                <p className="text-sm font-semibold text-ink">{label}</p>
                {hint && <p className="text-xs text-muted">{hint}</p>}
            </div>
            <div className="shrink-0">{children}</div>
        </div>
    );
}

function Switch({ checked, onChange }) {
    return (
        <button type="button" role="switch" aria-checked={checked} onClick={() => onChange(!checked)} className={`relative h-7 w-12 rounded-full ${checked ? 'bg-g700' : 'bg-line'}`}>
            <span className={`absolute top-1 h-5 w-5 rounded-full bg-white transition-all ${checked ? 'left-6' : 'left-1'}`} />
        </button>
    );
}

export default function Settings({ settings, regions }) {
    const { flash } = usePage().props;
    const form = useForm(settings);
    const { data, setData } = form;

    return (
        <AppLayout>
            <Head title={t("Sozlamalar")} />
            <h1 className="text-2xl font-extrabold text-ink">{t("Sozlamalar")}</h1>
            {flash?.success && <p className="mt-3 rounded-xl bg-g100 px-4 py-2 text-sm font-semibold text-g800">{flash.success}</p>}

            <section className={`mt-4 ${box}`}>
                <h2 className="font-bold text-ink">{t('Til')}</h2>
                <LanguageSwitcher className="mt-3" />
            </section>

            <form
                className="mt-4 space-y-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.put('/settings', { preserveScroll: true });
                }}
            >
                <section className={box}>
                    <h2 className="font-bold text-ink">{t("Kunlik rejalashtirish")}</h2>
                    <div className="grid grid-cols-3 gap-1 rounded-xl bg-bg p-1 text-sm font-bold">
                        {[
                            ['evening', tk('Kechqurun')],
                            ['morning', tk('Ertalab')],
                            ['off', tk("O'chiq")],
                        ].map(([value, label]) => (
                            <button
                                key={value}
                                type="button"
                                onClick={() => {
                                    setData((d) => ({ ...d, planning_mode: value, planning_time: value === 'morning' && d.planning_mode !== 'morning' ? '08:00' : value === 'evening' && d.planning_mode !== 'evening' ? '22:00' : d.planning_time }));
                                }}
                                className={`rounded-lg py-2 ${data.planning_mode === value ? 'bg-white text-g800 shadow-sm' : 'text-muted'}`}
                            >
                                {t(label)}
                            </button>
                        ))}
                    </div>
                    {data.planning_mode !== 'off' && (
                        <Row
                            label={t("Vaqti")}
                            hint={data.planning_mode === 'evening' ? t('Bot ertangi kunni rejalashtirishni eslatadi') : t('Bot bugungi kunni rejalashtirishni eslatadi')}
                        >
                            <input type="time" value={data.planning_time} onChange={(e) => setData('planning_time', e.target.value)} className={select} />
                        </Row>
                    )}
                    {data.planning_mode !== 'off' && <p className="text-xs text-muted">{t("Reja tuzilmasa, bot 30 daqiqadan keyin yana bir marta eslatadi.")}</p>}
                </section>

                <section className={box}>
                    <h2 className="font-bold text-ink">{t("Haftalik reja")}</h2>
                    <Row label={t("Kuni")}>
                        <select value={data.weekly_planning_day ?? ''} onChange={(e) => setData('weekly_planning_day', e.target.value ? Number(e.target.value) : null)} className={select}>
                            <option value="">{t("O'chiq")}</option>
                            {DAYS.map(([d, label]) => (
                                <option key={d} value={d}>
                                    {t(label)}
                                </option>
                            ))}
                        </select>
                    </Row>
                    {data.weekly_planning_day && (
                        <Row label={t("Vaqti")}>
                            <input type="time" value={data.weekly_planning_time} onChange={(e) => setData('weekly_planning_time', e.target.value)} className={select} />
                        </Row>
                    )}
                </section>

                <section className={box}>
                    <h2 className="font-bold text-ink">{t("Eslatmalar")}</h2>
                    <Row label={t("Namoz vaqti")} hint={t("Yoqilgan namozlar uchun")}>
                        <Switch checked={data.prayer_reminders} onChange={(v) => setData('prayer_reminders', v)} />
                    </Row>
                    {data.prayer_reminders && (
                        <Row label={t("Necha daqiqa oldin")}>
                            <select value={data.prayer_remind_before} onChange={(e) => setData('prayer_remind_before', Number(e.target.value))} className={select}>
                                {[0, 5, 10, 15, 20, 30].map((m) => (
                                    <option key={m} value={m}>
                                        {m ? t(':m daqiqa', { m }) : t('Vaqtida')}
                                    </option>
                                ))}
                            </select>
                        </Row>
                    )}
                    <Row label={t("Vaqtli vazifalar")}>
                        <Switch checked={data.task_reminders} onChange={(v) => setData('task_reminders', v)} />
                    </Row>
                    {data.task_reminders && (
                        <Row label={t("Necha daqiqa oldin")}>
                            <select value={data.task_remind_before} onChange={(e) => setData('task_remind_before', Number(e.target.value))} className={select}>
                                {[0, 5, 10, 15, 30, 60].map((m) => (
                                    <option key={m} value={m}>
                                        {m ? t(':m daqiqa', { m }) : t('Vaqtida')}
                                    </option>
                                ))}
                            </select>
                        </Row>
                    )}
                    <Row label={t("Kun yakuni")} hint={t("Har kuni 21:30 da qisqa xulosa")}>
                        <Switch checked={data.day_summary} onChange={(v) => setData('day_summary', v)} />
                    </Row>
                </section>

                <section className={box}>
                    <h2 className="font-bold text-ink">{t("Mintaqa")}</h2>
                    <Row label={t("Viloyat")} hint={t("Namoz vaqtlari shu bo'yicha")}>
                        <select value={data.region_id ?? ''} onChange={(e) => setData('region_id', Number(e.target.value))} className={`${select} max-w-44`}>
                            {regions.map((r) => (
                                <option key={r.id} value={r.id}>
                                    {r.name}
                                </option>
                            ))}
                        </select>
                    </Row>
                </section>

                {Object.values(form.errors).length > 0 && <p className="text-sm font-semibold text-red">{Object.values(form.errors)[0]}</p>}

                <button className="w-full rounded-xl bg-g700 py-3 font-bold text-white" disabled={form.processing}>
                    {t("Saqlash")}
                </button>
            </form>
        </AppLayout>
    );
}
