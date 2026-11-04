export default function LevelBar({ level, pointsToday = 0 }) {
    const percent = Math.round((level.into / level.needed) * 100);
    return (
        <section className="flex items-center gap-3 rounded-2xl bg-white p-4 ring-1 ring-line">
            <span className="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gold/15 text-xl font-extrabold text-gold">{level.level}</span>
            <div className="min-w-0 flex-1">
                <div className="flex items-baseline justify-between text-sm">
                    <span className="font-bold text-ink">{level.level}-daraja</span>
                    <span className="text-muted">
                        {level.into}/{level.needed} ball
                    </span>
                </div>
                <div className="mt-1.5 h-2 rounded-full bg-bg">
                    <div className="h-full rounded-full bg-gold" style={{ width: `${percent}%` }} />
                </div>
                <p className="mt-1 text-xs text-muted">
                    Jami {level.total} ball{pointsToday ? `, bugun +${pointsToday}` : ''}
                </p>
            </div>
        </section>
    );
}
