import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../Components/AppLayout';
import LanguageSwitcher from '../Components/LanguageSwitcher';
import LevelBar from '../Components/LevelBar';
import { formatPhone } from '../Components/PhoneInput';
import ThemeSwitcher from '../Components/ThemeSwitcher';
import { formatShort } from '../lib/dates';
import { t } from '../lib/i18n';
import { initials } from '../lib/initials';

const box = 'mt-4 rounded-2xl bg-card p-4 ring-1 ring-line';
const input = 'mt-1 w-full rounded-lg border border-line bg-card px-3 py-2 text-sm font-normal';

function Tile({ label, value }) {
    return (
        <div className="rounded-2xl bg-card p-3 text-center ring-1 ring-line">
            <p className="text-xl font-extrabold text-ink">{value}</p>
            <p className="text-xs font-semibold text-muted">{label}</p>
        </div>
    );
}

function NavRow({ href, label }) {
    return (
        <Link href={href} className="flex items-center justify-between py-3 text-sm font-semibold text-ink">
            {label}
            <span className="text-muted">›</span>
        </Link>
    );
}

export default function Profile({ profile, level, totals }) {
    const { flash, auth } = usePage().props;
    const demo = auth?.user?.is_demo;
    const form = useForm({ first_name: profile.first_name ?? '', last_name: profile.last_name ?? '', gender: profile.gender ?? '' });
    const { data, setData, errors } = form;

    return (
        <AppLayout>
            <Head title={t('Profil')} />
            <section className="flex items-center gap-4 rounded-2xl bg-g700 p-5 text-white">
                <span className="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-white/20 text-2xl font-extrabold">{initials(profile.first_name, profile.last_name)}</span>
                <div className="min-w-0">
                    <h1 className="truncate text-xl font-extrabold">{[profile.first_name, profile.last_name].filter(Boolean).join(' ')}</h1>
                    <p className="text-sm text-white/80">{[profile.region, profile.phone && formatPhone(profile.phone)].filter(Boolean).join(' · ')}</p>
                    {profile.telegram_username && <p className="text-sm text-white/80">@{profile.telegram_username}</p>}
                    {profile.member_since && <p className="text-xs text-white/70">{t(':date dan beri', { date: formatShort(profile.member_since) })}</p>}
                </div>
            </section>

            <div className="mt-4">
                <LevelBar level={level} />
            </div>

            <div className="mt-4 grid grid-cols-3 gap-2">
                <Tile label={t('Bajarilgan vazifa')} value={totals.tasks_done} />
                <Tile label={t("O'qish, soat")} value={(totals.study_minutes / 60).toFixed(1)} />
                <Tile label={t('Tugatilgan kitob')} value={totals.books_finished} />
            </div>

            {flash?.success && <p className="mt-4 rounded-xl bg-g100 px-4 py-2 text-sm font-semibold text-g800">{flash.success}</p>}

            <form
                className={`${box} space-y-3`}
                onSubmit={(e) => {
                    e.preventDefault();
                    form.put('/profile', { preserveScroll: true });
                }}
            >
                <h2 className="font-bold text-ink">{t("Ma'lumotlarim")}</h2>
                <div className="grid grid-cols-2 gap-2">
                    <label className="block text-sm font-semibold text-ink">
                        {t('Ism')}
                        <input value={data.first_name} onChange={(e) => setData('first_name', e.target.value)} maxLength={60} className={input} />
                    </label>
                    <label className="block text-sm font-semibold text-ink">
                        {t('Familiya')}
                        <input value={data.last_name} onChange={(e) => setData('last_name', e.target.value)} maxLength={60} className={input} />
                    </label>
                </div>
                {!profile.gender && (
                    <div className="text-sm font-semibold text-ink">
                        {t('Jins')}
                        <div className="mt-1 grid grid-cols-2 gap-1 rounded-xl bg-bg p-1 font-bold">
                            {[
                                ['male', t('Erkak')],
                                ['female', t('Ayol')],
                            ].map(([value, label]) => (
                                <button key={value} type="button" onClick={() => setData('gender', value)} className={`rounded-lg py-2 ${data.gender === value ? 'bg-card text-g800 shadow-sm' : 'text-muted'}`}>
                                    {label}
                                </button>
                            ))}
                        </div>
                    </div>
                )}
                {Object.values(errors).length > 0 && <p className="text-sm font-semibold text-red">{Object.values(errors)[0]}</p>}
                <button className="w-full rounded-xl bg-g700 py-2.5 text-sm font-bold text-white" disabled={form.processing || !data.first_name.trim()}>
                    {t('Saqlash')}
                </button>
            </form>

            <section className={box}>
                <h2 className="font-bold text-ink">{t("Ko'rinish")}</h2>
                <ThemeSwitcher className="mt-3" />
                <LanguageSwitcher className="mt-2" />
            </section>

            <nav className={`${box} divide-y divide-line py-1`}>
                <NavRow href="/stats" label={t('Statistika')} />
                <NavRow href="/gym" label={t('Zal')} />
                <NavRow href="/settings" label={t('Sozlamalar')} />
            </nav>

            <button type="button" onClick={() => router.post(demo ? '/demo/exit' : '/logout')} className="mt-4 w-full rounded-xl py-3 font-bold text-red ring-1 ring-line">
                {demo ? t("Ro'yxatdan o'tish") : t('Chiqish')}
            </button>
        </AppLayout>
    );
}
