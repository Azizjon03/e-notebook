import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import HeaderControls from '../Components/HeaderControls';
import PhoneInput from '../Components/PhoneInput';
import { isMiniApp, webApp } from '../telegram/webapp';
import { t } from '../lib/i18n';

const field = 'mt-1 w-full rounded-lg border border-line px-3 py-2.5 font-normal';

export default function Login({ devLogin = false }) {
    const { errors } = usePage().props;
    const [trying, setTrying] = useState(false);
    const form = useForm({ phone: '+998', password: '' });

    useEffect(() => {
        // Mini App ichida initData bor: avtomatik kiramiz.
        if (!isMiniApp()) return;
        setTrying(true);
        router.post(
            '/auth/telegram/webapp',
            { init_data: webApp().initData },
            { onFinish: () => setTrying(false) },
        );
    }, []);

    return (
        <div className="mx-auto grid min-h-screen max-w-md place-items-center px-4">
            <Head title={t("Kirish")} />
            <div className="relative w-full rounded-2xl bg-card p-6 text-center shadow-sm ring-1 ring-line">
                <HeaderControls className="absolute top-4 right-4" />
                <span className="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-g700 text-2xl font-extrabold text-white">
                    e
                </span>
                <h1 className="mt-4 text-2xl font-extrabold text-ink">e-notebook</h1>
                {trying ? (
                    <p className="mt-3 text-muted">{t("Telegram orqali kirilmoqda...")}</p>
                ) : (
                    <form
                        className="mt-5 space-y-2 text-left"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post('/login', { onFinish: () => form.reset('password') });
                        }}
                    >
                        <label className="block text-sm font-semibold text-ink">
                            {t('Telefon raqam')}
                            <PhoneInput value={form.data.phone} onChange={(v) => form.setData('phone', v)} className={field} autoFocus />
                        </label>
                        <label className="block text-sm font-semibold text-ink">
                            {t('Parol')}
                            <input type="password" autoComplete="current-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} className={field} />
                        </label>
                        {form.errors.phone && <p className="text-sm font-semibold text-red">{form.errors.phone}</p>}
                        <button className="w-full rounded-xl bg-g700 py-3 font-bold text-white" disabled={form.processing || !form.data.password}>
                            {t('Kirish')}
                        </button>
                        <p className="pt-1 text-center text-sm text-muted">
                            {t("Hisobingiz yo'qmi?")}{' '}
                            <Link href="/register" className="font-bold text-g700">
                                {t("Ro'yxatdan o'tish")}
                            </Link>
                        </p>
                        <button type="button" onClick={() => router.post('/demo')} className="w-full rounded-xl py-3 font-bold text-g700 ring-1 ring-line">
                            {t("Demoni ko'rish")}
                        </button>
                        <p className="text-center text-xs text-muted">{t('Yoki ilovani Telegram bot ichidagi "Ilovani ochish" tugmasi orqali oching.')}</p>
                    </form>
                )}
                {errors?.init_data && <p className="mt-3 text-sm font-semibold text-red">{errors.init_data}</p>}
                {devLogin && !trying && (
                    <div className="mt-6 border-t border-line pt-4">
                        <p className="text-xs font-semibold uppercase tracking-wide text-muted">{t("Faqat kompyuterda sinash uchun")}</p>
                        <div className="mt-3 grid grid-cols-2 gap-2">
                            <button type="button" onClick={() => router.post('/auth/dev', { gender: 'male' })} className="rounded-xl bg-g700 py-2.5 text-sm font-bold text-white">
                                {t("Erkak sifatida")}
                            </button>
                            <button type="button" onClick={() => router.post('/auth/dev', { gender: 'female' })} className="rounded-xl bg-g700 py-2.5 text-sm font-bold text-white">
                                {t("Ayol sifatida")}
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
