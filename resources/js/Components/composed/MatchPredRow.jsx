import ScoreBox from './ScoreBox';

export default function MatchPredRow({
    date, venue,
    teamHome, teamAway,
    flagUrlHome, flagUrlAway,
    scoreHome, scoreAway,
    status,
    last = false,
}) {
    const filled = status === 'ok';

    return (
        <div className={[
            'px-2.5 py-2 relative',
            !last ? 'border-b border-dashed border-black/20' : '',
        ].join(' ')}>
            <div className="font-mono text-[8.5px] tracking-[.08em] opacity-55 mb-1">
                {date} · {venue}
            </div>
            <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                <div className="flex items-center justify-end gap-1.5">
                    <span className="font-display text-[12px]">{teamHome}</span>
                    <img src={flagUrlHome} alt={teamHome} className="h-4 w-6 object-cover border border-ink" />
                </div>
                <div className="flex items-center gap-0.5">
                    <ScoreBox value={scoreHome} filled={filled} />
                    <span className="font-display text-[13px] opacity-55 mx-0.5">—</span>
                    <ScoreBox value={scoreAway} filled={filled} />
                </div>
                <div className="flex items-center gap-1.5">
                    <img src={flagUrlAway} alt={teamAway} className="h-4 w-6 object-cover border border-ink" />
                    <span className="font-display text-[12px]">{teamAway}</span>
                </div>
            </div>
            <div className="flex justify-center mt-1">
                {filled ? (
                    <span className="inline-flex items-center gap-1 font-mono text-[8.5px] font-bold tracking-[.08em] bg-pop-teal text-white px-1.5 py-0.5 border-[1.5px] border-ink">
                        ✓ GUARDADO
                    </span>
                ) : (
                    <span className="inline-flex items-center gap-1 font-mono text-[8.5px] font-bold tracking-[.08em] bg-white text-pop-red px-1.5 py-0.5 border-[1.5px] border-dashed border-pop-red">
                        ! FALTAN TUS GOLES
                    </span>
                )}
            </div>
        </div>
    );
}
