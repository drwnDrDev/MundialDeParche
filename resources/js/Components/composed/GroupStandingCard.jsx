export default function GroupStandingCard({ group, played, teams }) {
    return (
        <div className="border-2.5 border-ink shadow-pop-md bg-white relative overflow-hidden">
            {/* Header */}
            <div className="flex justify-between items-center px-3 py-1.5 bg-pop-red text-white border-b-[2.5px] border-ink">
                <div className="font-display text-[14px]">GRUPO {group}</div>
                <div className="font-mono text-[9px] tracking-[.06em] opacity-90">{played}</div>
            </div>

            {/* Column headers */}
            <div className="grid grid-cols-[20px_1fr_24px_50px_28px_28px] gap-1 px-2.5 py-1.5 font-mono text-[9px] font-bold tracking-[.06em] opacity-55 border-b border-dashed border-black/20">
                <span>#</span>
                <span>EQUIPO</span>
                <span className="text-center">PJ</span>
                <span className="text-center">G-E-P</span>
                <span className="text-center">GD</span>
                <span className="text-right">PTS</span>
            </div>

            {/* Teams */}
            {teams.map((t, i) => {
                const gd = t.gf - t.gc;
                const isTop  = i < 2;
                const isLast = i === teams.length - 1;
                return (
                    <div
                        key={i}
                        className={[
                            'grid grid-cols-[20px_1fr_24px_50px_28px_28px] gap-1 px-2.5 py-2 items-center',
                            !isLast ? 'border-b border-dashed border-black/10' : '',
                        ].join(' ')}
                        style={isTop ? { background: 'rgba(255,210,63,.18)' } : {}}
                    >
                        <span className="font-mono font-bold text-[11px] opacity-70">
                            {i + 1}°{isTop && <span className="ml-0.5 text-[8px] text-pop-teal">↑</span>}
                        </span>
                        <div className="flex items-center gap-1.5">
                            <img src={t.flagUrl} alt={t.name} className="h-3 w-[18px] object-cover" />
                            <span className="font-display text-[11px] tracking-[.02em]">{t.name}</span>
                            {t.live && (
                                <span className="font-mono text-[7px] font-bold text-pop-red tracking-[.06em] px-0.5 border border-pop-red leading-tight">
                                    LIVE
                                </span>
                            )}
                        </div>
                        <span className="font-mono font-bold text-[11px] text-center">{t.pj}</span>
                        <span className="font-mono text-[10px] text-center tracking-[.04em]">
                            {t.g}-{t.e}-{t.p}
                        </span>
                        <span className={[
                            'font-mono text-[10px] text-center font-bold',
                            gd > 0 ? 'text-pop-teal' : gd < 0 ? 'text-pop-red' : 'text-ink',
                        ].join(' ')}>
                            {gd >= 0 ? '+' : ''}{gd}
                        </span>
                        <span className="font-display text-[14px] text-right">{t.pts}</span>
                    </div>
                );
            })}

            {/* Footer */}
            <div className="px-2.5 py-1.5 bg-black/[.04] font-mono text-[9px] opacity-65 tracking-[.06em] flex justify-between">
                <span>TOP 2 → R32</span>
                <span>+ 8 mejores 3°</span>
            </div>
        </div>
    );
}
