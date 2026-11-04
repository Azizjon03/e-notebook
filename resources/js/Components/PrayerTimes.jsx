export default function PrayerTimes({ prayers, now, isToday }) {
    if (!prayers) {
        return null;
    }

    // Keyingi namoz: vaqti hozirdan keyin bo'lgan birinchisi (quyosh chiqishi namoz emas).
    const next = isToday ? prayers.find((p) => p.key !== 'sunrise' && p.time > now) : null;

    return (
        <section className="mt-4 rounded-2xl bg-white p-4 ring-1 ring-line">
            <div className="mb-3 flex items-baseline justify-between">
                <h2 className="font-bold text-ink">Namoz vaqtlari</h2>
                {next && (
                    <span className="text-sm font-semibold text-g700">
                        Keyingisi: {next.name} {next.time}
                    </span>
                )}
            </div>
            <ul className="grid grid-cols-6 gap-1 text-center">
                {prayers.map((p) => {
                    const active = next?.key === p.key;
                    return (
                        <li key={p.key} className={`rounded-xl py-2 ${active ? 'bg-g700 text-white' : 'bg-bg'}`}>
                            <p className={`text-[11px] font-semibold ${active ? 'text-white/80' : 'text-muted'}`}>{p.name}</p>
                            <p className={`text-sm font-bold ${active ? 'text-white' : p.key === 'sunrise' ? 'text-muted' : 'text-ink'}`}>
                                {p.time}
                            </p>
                        </li>
                    );
                })}
            </ul>
        </section>
    );
}
