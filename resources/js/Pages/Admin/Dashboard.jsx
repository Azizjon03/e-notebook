import { Head } from '@inertiajs/react';
import AdminLayout, { Card } from '../../Components/AdminLayout';

const STEP = { name: 'Ism kutilmoqda', location: 'Joylashuv kutilmoqda', gender: 'Jins kutilmoqda', done: 'Tayyor' };

function Stat({ label, value, hint }) {
    return (
        <div className="rounded-2xl bg-card p-4 ring-1 ring-line">
            <p className="text-sm font-medium text-muted">{label}</p>
            <p className="mt-1 text-3xl font-extrabold text-ink">{value}</p>
            {hint && <p className="mt-1 text-xs text-muted">{hint}</p>}
        </div>
    );
}

export default function Dashboard({ stats, byRegion, latest }) {
    const max = Math.max(1, ...byRegion.map((r) => r.total));

    return (
        <AdminLayout title="Umumiy">
            <Head title="Admin" />
            <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                <Stat label="Foydalanuvchilar" value={stats.users} hint={`${stats.onboarded} tasi ro'yxatdan to'liq o'tgan`} />
                <Stat label="Bugun qo'shildi" value={stats.new_today} />
                <Stat label="Oxirgi 7 kun" value={stats.new_week} />
                <Stat label="Bugungi vazifalar" value={stats.tasks_today} hint={`${stats.tasks_done_today} tasi bajarildi`} />
            </div>

            <div className="mt-5 grid gap-5 md:grid-cols-2">
                <Card title="Viloyatlar bo'yicha">
                    {byRegion.length === 0 && <p className="text-muted">Hali foydalanuvchi yo'q.</p>}
                    <ul className="space-y-2">
                        {byRegion.map((r) => (
                            <li key={r.name}>
                                <div className="flex justify-between text-sm">
                                    <span className="text-ink">{r.name}</span>
                                    <span className="font-semibold text-ink">{r.total}</span>
                                </div>
                                <div className="mt-1 h-1.5 rounded-full bg-g100">
                                    <div className="h-full rounded-full bg-g700" style={{ width: `${(r.total / max) * 100}%` }} />
                                </div>
                            </li>
                        ))}
                    </ul>
                </Card>

                <Card title="Oxirgi qo'shilganlar">
                    <ul className="divide-y divide-line">
                        {latest.map((u) => (
                            <li key={u.id} className="flex items-center justify-between gap-3 py-2 text-sm">
                                <div className="min-w-0">
                                    <p className="truncate font-semibold text-ink">{u.name}</p>
                                    <p className="truncate text-muted">
                                        {u.username ? `@${u.username}` : '—'} · {u.region ?? 'mintaqa yo\'q'}
                                    </p>
                                </div>
                                <div className="shrink-0 text-right">
                                    <p className={u.step === 'done' ? 'text-g700' : 'text-gold'}>{STEP[u.step] ?? u.step}</p>
                                    <p className="text-xs text-muted">{u.joined}</p>
                                </div>
                            </li>
                        ))}
                    </ul>
                </Card>
            </div>
        </AdminLayout>
    );
}
