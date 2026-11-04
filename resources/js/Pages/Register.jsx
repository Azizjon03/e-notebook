import { Head, Link, useForm, usePage } from '@inertiajs/react';
import HeaderControls from '../Components/HeaderControls';
import PhoneInput from '../Components/PhoneInput';
import { t } from '../lib/i18n';

const field = 'mt-1 w-full rounded-lg border border-line bg-card px-3 py-2.5 font-normal';

function Field({ label, error, children }) {
    return (
        <label className="block text-sm font-semibold text-ink">
            {label}
            {children}
            {error && <span className="mt-1 block font-semibold text-red">{error}</span>}
        </label>
    );
}

export default function Register({ regions }) {
    const { flash } = usePage().props;
    const form = useForm({ first_name: '', last_name: '', phone: '+998', password: '', password_confirmation: '', gender: '', region_id: '' });
    const { data, setData, errors } = form;
    const tooShort = data.password.length > 0 && data.password.length < 8;

    return (
        <div className="mx-auto max-w-md px-4 py-8">
            <Head title={t("Ro'yxatdan o'tish")} />
            <div className="rounded-2xl bg-card p-6 shadow-sm ring-1 ring-line">
                <div className="flex items-center justify-between gap-2">
                    <h1 className="text-2xl font-extrabold text-ink">{t("Ro'yxatdan o'tish")}</h1>
                    <HeaderControls />
                </div>
                {flash?.error && <p className="mt-3 rounded-xl bg-gold/15 px-4 py-2 text-sm font-semibold text-ink">{flash.error}</p>}
                <form
                    className="mt-5 space-y-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
                    }}
                >
                    <div className="grid grid-cols-2 gap-2">
                        <Field label={t('Ism')} error={errors.first_name}>
                            <input value={data.first_name} onChange={(e) => setData('first_name', e.target.value)} maxLength={60} autoComplete="given-name" className={field} />
                        </Field>
                        <Field label={t('Familiya')} error={errors.last_name}>
                            <input value={data.last_name} onChange={(e) => setData('last_name', e.target.value)} maxLength={60} autoComplete="family-name" className={field} />
                        </Field>
                    </div>
                    <Field label={t('Telefon raqam')} error={errors.phone}>
                        <PhoneInput value={data.phone} onChange={(v) => setData('phone', v)} className={field} />
                    </Field>
                    <Field label={t('Parol')} error={errors.password}>
                        <input type="password" autoComplete="new-password" value={data.password} onChange={(e) => setData('password', e.target.value)} className={field} />
                        <span className={`mt-1 block text-xs font-normal ${tooShort ? 'text-red' : 'text-muted'}`}>{t('Kamida 8 belgi')}</span>
                    </Field>
                    <Field label={t('Parolni takrorlang')}>
                        <input type="password" autoComplete="new-password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} className={field} />
                    </Field>
                    <Field label={t('Jins')} error={errors.gender}>
                        <div className="mt-1 grid grid-cols-2 gap-1 rounded-xl bg-bg p-1 text-sm font-bold">
                            {[
                                ['male', t('Erkak')],
                                ['female', t('Ayol')],
                            ].map(([value, label]) => (
                                <button key={value} type="button" onClick={() => setData('gender', value)} className={`rounded-lg py-2 ${data.gender === value ? 'bg-card text-g800 shadow-sm' : 'text-muted'}`}>
                                    {label}
                                </button>
                            ))}
                        </div>
                    </Field>
                    <Field label={t('Viloyat')} error={errors.region_id}>
                        <select value={data.region_id} onChange={(e) => setData('region_id', e.target.value)} className={field}>
                            <option value="">{t('Tanlang')}</option>
                            {regions.map((r) => (
                                <option key={r.id} value={r.id}>
                                    {r.name}
                                </option>
                            ))}
                        </select>
                    </Field>
                    <button className="w-full rounded-xl bg-g700 py-3 font-bold text-white" disabled={form.processing}>
                        {t("Ro'yxatdan o'tish")}
                    </button>
                    <p className="text-center text-sm text-muted">
                        {t('Hisobingiz bormi?')}{' '}
                        <Link href="/login" className="font-bold text-g700">
                            {t('Kirish')}
                        </Link>
                    </p>
                </form>
            </div>
        </div>
    );
}
