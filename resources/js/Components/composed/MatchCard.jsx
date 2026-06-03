export default function MatchCard({
    status,
    time,
    teamA, teamB,
    flagUrlA, flagUrlB,
    scoreA, scoreB,
    minute,
    group, venue,
    myPick, myPts,
    matchNumber, fifaRound, wentToET, winner,
}) {
    const isLive = status === 'in_progress' || status === 'live';
    const isFT   = status === 'finished' || status === 'ft';
    const isUp   = !isLive && !isFT;

    const matchInfo = fifaRound === 'grupos'
        ? `GRUPO ${group} · ${venue}`
        : `M${matchNumber} · ${venue}`;

    return (
        <div className={[
            'border-[2.5px] border-ink shadow-pop p-[10px_12px] relative overflow-hidden',
            isLive ? 'bg-navy text-cream' : 'bg-white text-ink',
        ].join(' ')}>
            {isLive && (
                <div className="halftone halftone-red absolute inset-0 opacity-15 pointer-events-none" />
            )}

            <div className="flex items-center gap-2 relative">
                {/* Status indicator */}
                <div className="w-[52px] text-center flex-shrink-0">
                    {isLive && (
                        <div>
                            <div className="font-display text-[11px] text-pop-red flex items-center gap-1 justify-center">
                                <span className="w-1.5 h-1.5 rounded-full bg-pop-red animate-pulse" />
                                LIVE
                            </div>
                            <div className="font-display text-[13px] text-pop-yel mt-0.5">{minute}</div>
                        </div>
                    )}
                    {isFT && (
                        <div>
                            <div className="font-display text-[13px] text-pop-teal">FT</div>
                            <div className="font-mono text-[9px] opacity-55 mt-0.5">{time}</div>
                        </div>
                    )}
                    {isUp && (
                        <div className="font-display text-[13px]">{time}</div>
                    )}
                </div>

                {/* Teams + score */}
                <div className="flex-1 grid grid-cols-[1fr_auto_1fr] items-center gap-1.5">
                    <div className="flex items-center gap-1.5 justify-end">
                        <span className="font-display text-[13px]">{teamA}</span>
                        {flagUrlA && <img src={flagUrlA} alt={teamA} className="h-4 w-6 object-cover border border-ink" />}
                    </div>
                    <div className="flex items-center gap-1 px-1">
                        {isUp ? (
                            <span className="font-display text-[14px] opacity-50">VS</span>
                        ) : (
                            <>
                                <span className={`font-display text-[20px] ${isLive ? 'text-pop-yel' : 'text-ink'}`}>{scoreA}</span>
                                <span className="opacity-50 mx-0.5">—</span>
                                <span className={`font-display text-[20px] ${isLive ? 'text-cream' : 'text-ink'}`}>{scoreB}</span>
                            </>
                        )}
                    </div>
                    <div className="flex items-center gap-1.5">
                        {flagUrlB && <img src={flagUrlB} alt={teamB} className="h-4 w-6 object-cover border border-ink" />}
                        <span className="font-display text-[13px]">{teamB}</span>
                    </div>
                </div>
            </div>

            {/* Footer */}
            <div className={[
                'mt-2 pt-2 flex items-center justify-between gap-1.5',
                'font-mono text-[9px] font-bold tracking-[.06em]',
                isLive
                    ? 'border-t border-dashed border-cream/30'
                    : 'border-t border-dashed border-black/20',
            ].join(' ')}>
                <div className="flex flex-col gap-0.5">
                    <span className={isLive ? 'opacity-80' : 'opacity-65'}>{matchInfo}</span>
                    {isFT && wentToET && winner && (
                        <span className="inline-flex items-center gap-1 px-1.5 py-0.5 border-[1.5px] border-ink bg-pop-yel text-ink">
                            {winner} · ET/PEN
                        </span>
                    )}
                </div>
                {myPick ? (
                    <span className={[
                        'inline-flex items-center gap-1 px-1.5 py-0.5 border-[1.5px] border-ink flex-shrink-0',
                        isFT && myPts != null
                            ? 'bg-pop-teal text-white'
                            : 'bg-pop-yel text-ink',
                    ].join(' ')}>
                        TUS GOLES: {myPick}{isFT && myPts != null ? ` · +${myPts} PTS` : ''}
                    </span>
                ) : (
                    <span className={[
                        'px-1.5 py-0.5 border-[1.5px] border-dashed flex-shrink-0',
                        isLive
                            ? 'border-cream/60 text-cream'
                            : 'border-pop-red text-pop-red',
                    ].join(' ')}>
                        ! FALTAN TUS GOLES
                    </span>
                )}
            </div>
        </div>
    );
}
