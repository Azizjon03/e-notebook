import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout, { Card } from '../../Components/AdminLayout';

const input = 'w-full rounded-xl border border-line bg-bg px-3 py-2 text-ink outline-none focus:border-g500';
const button = 'rounded-xl bg-g700 px-4 py-2 font-bold text-white disabled:opacity-40';

function Field({ label, error, children }) {
    return (
        <label className="block">
            <span className="mb-1 block text-sm font-semibold text-ink">{label}</span>
            {children}
            {error && <span className="mt-1 block text-sm text-red">{error}</span>}
        </label>
    );
}

function StatusCard({ status, secretConfigured }) {
    if (!status.configured) {
        return (
            <Card title="Holat">
                <p className="text-ink">
                    Bot tokeni sozlanmagan. Serverdagi <code>.env</code> fayliga <code>TELEGRAM_BOT_TOKEN</code> ni yozing va
                    <code> php artisan config:cache</code> ni ishga tushiring. Token xavfsizlik uchun bu sahifadan kiritilmaydi.
                </p>
            </Card>
        );
    }

    const wh = status.webhook;
    const ok = wh.url === wh.expected_url;

    return (
        <Card title="Holat">
            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt className="text-muted">Bot</dt>
                    <dd className="font-semibold text-ink">
                        {status.name} {status.username && <a className="text-g700" href={`https://t.me/${status.username}`}>@{status.username}</a>}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted">Token</dt>
                    <dd className="font-semibold text-g700">Sozlangan (.env)</dd>
                </div>
                <div className="sm:col-span-2">
                    <dt className="text-muted">Webhook</dt>
                    <dd className={`font-semibold break-all ${ok ? 'text-g700' : 'text-red'}`}>
                        {wh.url || "o'rnatilmagan"} {!ok && <span className="block font-normal text-muted">Kerakli manzil: {wh.expected_url}</span>}
                    </dd>
                </div>
                <div>
                    <dt className="text-muted">Navbatdagi xabarlar</dt>
                    <dd className="font-semibold text-ink">{wh.pending}</dd>
                </div>
                <div>
                    <dt className="text-muted">Oxirgi xato</dt>
                    <dd className={wh.last_error ? 'text-red' : 'text-ink'}>
                        {wh.last_error ? `${wh.last_error} (${wh.last_error_at})` : "Yo'q"}
                    </dd>
                </div>
            </dl>
            <button
                type="button"
                disabled={!secretConfigured}
                onClick={() => router.post('/admin/bot/webhook', {}, { preserveScroll: true })}
                className={`${button} mt-4`}
            >
                Webhookni qayta o'rnatish
            </button>
            {!secretConfigured && <p className="mt-2 text-sm text-red">.env da TELEGRAM_WEBHOOK_SECRET yo'q.</p>}
        </Card>
    );
}

function ProfileCard({ status }) {
    const form = useForm({
        name: status.name ?? '',
        description: status.description ?? '',
        short_description: status.short_description ?? '',
    });

    return (
        <Card title="Bot profili">
            <form
                className="space-y-3"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post('/admin/bot/profile', { preserveScroll: true });
                }}
            >
                <Field label="Nomi" error={form.errors.name}>
                    <input className={input} value={form.data.name} maxLength={64} onChange={(e) => form.setData('name', e.target.value)} />
                </Field>
                <Field label="Qisqa tavsif (profilda)" error={form.errors.short_description}>
                    <input
                        className={input}
                        value={form.data.short_description}
                        maxLength={120}
                        onChange={(e) => form.setData('short_description', e.target.value)}
                    />
                </Field>
                <Field label="Tavsif (bo'sh chatda /start dan oldin)" error={form.errors.description}>
                    <textarea
                        className={input}
                        rows={3}
                        value={form.data.description}
                        maxLength={512}
                        onChange={(e) => form.setData('description', e.target.value)}
                    />
                </Field>
                <button className={button} disabled={form.processing}>
                    Saqlash
                </button>
            </form>
        </Card>
    );
}

function CommandsCard({ status, defaultCommands }) {
    const form = useForm({ commands: status.commands?.length ? status.commands : defaultCommands });
    const set = (i, key, value) => form.setData('commands', form.data.commands.map((c, j) => (j === i ? { ...c, [key]: value } : c)));

    return (
        <Card title="Buyruqlar (menyu)">
            <form
                className="space-y-2"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post('/admin/bot/commands', { preserveScroll: true });
                }}
            >
                {form.data.commands.map((c, i) => (
                    <div key={i} className="flex gap-2">
                        <span className="self-center text-muted">/</span>
                        <input className={`${input} w-32`} value={c.command} onChange={(e) => set(i, 'command', e.target.value)} />
                        <input className={input} value={c.description} onChange={(e) => set(i, 'description', e.target.value)} />
                        <button
                            type="button"
                            onClick={() => form.setData('commands', form.data.commands.filter((_, j) => j !== i))}
                            className="px-2 text-red"
                            aria-label="Olib tashlash"
                        >
                            ×
                        </button>
                    </div>
                ))}
                {Object.entries(form.errors).map(([k, v]) => (
                    <p key={k} className="text-sm text-red">
                        {v}
                    </p>
                ))}
                <div className="flex gap-2 pt-2">
                    <button
                        type="button"
                        onClick={() => form.setData('commands', [...form.data.commands, { command: '', description: '' }])}
                        className="rounded-xl px-3 py-2 font-semibold text-g700 ring-1 ring-line"
                    >
                        + Buyruq
                    </button>
                    <button className={button} disabled={form.processing}>
                        Saqlash
                    </button>
                </div>
            </form>
        </Card>
    );
}

const LANGS = { uz: "O'zbekcha", ru: 'Русский', en: 'English' };

function TextsCard({ texts, defaults, labels, textLocale }) {
    const form = useForm({ texts, locale: textLocale });

    return (
        <Card title="Bot xabarlari" className="md:col-span-2">
            <div className="mb-3 flex gap-2">
                {Object.entries(LANGS).map(([code, name]) => (
                    <Link
                        key={code}
                        href={`/admin/bot?lang=${code}`}
                        preserveScroll
                        className={`rounded-lg px-3 py-1.5 text-sm font-semibold ${code === textLocale ? 'bg-g700 text-white' : 'text-ink ring-1 ring-line'}`}
                    >
                        {name}
                    </Link>
                ))}
            </div>
            <p className="mb-4 text-sm text-muted">
                <code>{'{name}'}</code> o'rniga foydalanuvchi ismi qo'yiladi. HTML teglar: &lt;b&gt;, &lt;i&gt;. Bo'sh qoldirilsa standart matn ishlatiladi.
            </p>
            <form
                className="grid gap-4 md:grid-cols-2"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post('/admin/bot/texts', { preserveScroll: true });
                }}
            >
                {Object.keys(defaults).map((key) => (
                    <Field key={key} label={labels[key]} error={form.errors[`texts.${key}`]}>
                        <textarea
                            className={input}
                            rows={key.endsWith('button') ? 1 : 3}
                            value={form.data.texts[key] ?? ''}
                            placeholder={defaults[key]}
                            onChange={(e) => form.setData('texts', { ...form.data.texts, [key]: e.target.value })}
                        />
                        {form.data.texts[key] !== defaults[key] && (
                            <button
                                type="button"
                                className="mt-1 text-xs font-semibold text-muted"
                                onClick={() => form.setData('texts', { ...form.data.texts, [key]: defaults[key] })}
                            >
                                Standartga qaytarish
                            </button>
                        )}
                    </Field>
                ))}
                <div className="md:col-span-2">
                    <button className={button} disabled={form.processing}>
                        Matnlarni saqlash
                    </button>
                </div>
            </form>
        </Card>
    );
}

export default function Bot({ status, secretConfigured, texts, defaults, labels, defaultCommands, textLocale }) {
    return (
        <AdminLayout title="Bot sozlamalari">
            <Head title="Bot sozlamalari" />
            <div className="grid gap-5 md:grid-cols-2">
                <StatusCard status={status} secretConfigured={secretConfigured} />
                {status.configured && <ProfileCard status={status} />}
                {status.configured && <CommandsCard status={status} defaultCommands={defaultCommands} />}
                <TextsCard key={textLocale} texts={texts} defaults={defaults} labels={labels} textLocale={textLocale} />
            </div>
        </AdminLayout>
    );
}
