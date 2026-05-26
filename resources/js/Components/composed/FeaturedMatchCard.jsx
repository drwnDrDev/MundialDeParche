import Burst from '@/Components/ui/Burst';
import { GoalNet, SoccerBall } from '@/Components/icons/football';

export default function FeaturedMatchCard({
    status,
    teamA, teamB,
    codeA, codeB,
    flagUrlA, flagUrlB,
    scoreA, scoreB,
    group, venue,
    matchDate,
    myPick, myPts,
    isWinnerCorrect,
}) {
    const isLive = status === 'live';

    let timeLabel = '';
    if (!isLive && matchDate) {
        const d = new Date(matchDate);
        const h = d.getHours().toString().padStart(2, '0');
        const m = d.getMinutes().toString().padStart(2, '0');
        timeLabel = `HOY · ${h}:${m}`;
    }

    return (
        <div className="relative pt-3">
            {/* Top badge — sits above the card border */}
            {isLive ? (
                <div className="absolute top-0 left-3.5 z-10 bg-pop-red text-white border-[2.5px] border-ink px-2.5 py-0.5 font-display text-[11px] -rotate-3 shadow-pop-sm flex items-center gap-1.5">
                    <span className="w-[7px] h-[7px] rounded-full bg-pop-yel animate-[blink_1.2s_ease-in-out_infinite]" />
                    EN VIVO
                </div>
            ) : (
                <div className="absolute top-0 left-3.5 z-10 bg-pop-yel text-ink border-[2.5px] border-ink px-2.5 py-0.5 font-display text-[11px] -rotate-3 shadow-pop-sm">
                    {timeLabel}
                </div>
            )}

            {/* Burst — top-right */}
            <div className="absolute -top-0.5 -right-2 z-10">
                <Burst color={isLive ? 'yel' : 'red'} size="sm" rotate={isLive ? 14 : 12}>
                    {isLive ? '¡VAMOS!' : 'EN 2H'}
                </Burst>
            </div>

            {/* Card */}
            <div className="border-[3px] border-ink shadow-pop-xl rounded-[3px] bg-navy text-cream p-4 overflow-hidden relative">
                {/* GoalNet bg */}
                <div className="absolute top-2 left-2 right-2 bottom-20 opacity-15 pointer-events-none">
                    <GoalNet size={120} stroke="var(--c-cream)" />
                </div>

                {/* Score / VS row */}
                <div className="relative flex items-center justify-between mt-3 px-1">
                    {/* Team A */}
                    <div className="text-center flex-1">
                        {flagUrlA ? (
                            <img src={flagUrlA} alt={codeA} className="h-10 w-14 object-cover border border-white/30 mx-auto" />
                        ) : (
                            <div className="h-10 w-14 bg-white/10 border border-white/30 mx-auto flex items-center justify-center font-mono text-[10px]">
                                {codeA}
                            </div>
                        )}
                        <div className="font-display text-[18px] mt-1.5">{codeA}</div>
                    </div>

                    {/* Center: score or VS */}
                    {isLive ? (
                        <div className="flex items-center gap-2">
                            <div
                                className="font-display text-[44px] leading-none text-pop-yel"
                                style={{ WebkitTextStroke: '2px var(--c-ink)' }}
                            >
                                {scoreA ?? 0}
                            </div>
                            <div className="font-display text-[26px] text-cream opacity-50">—</div>
                            <div
                                className="font-display text-[44px] leading-none text-cream"
                                style={{ WebkitTextStroke: '2px var(--c-ink)' }}
                            >
                                {scoreB ?? 0}
                            </div>
                        </div>
                    ) : (
                        <div className="relative">
                            <div className="font-display text-[28px] text-pop-yel">VS</div>
                            <div className="absolute -top-1.5 left-1/2 -translate-x-1/2">
                                <SoccerBall size={26} />
                            </div>
                        </div>
                    )}

                    {/* Team B */}
                    <div className="text-center flex-1">
                        {flagUrlB ? (
                            <img src={flagUrlB} alt={codeB} className="h-10 w-14 object-cover border border-white/30 mx-auto" />
                        ) : (
                            <div className="h-10 w-14 bg-white/10 border border-white/30 mx-auto flex items-center justify-center font-mono text-[10px]">
                                {codeB}
                            </div>
                        )}
                        <div className="font-display text-[18px] mt-1.5">{codeB}</div>
                    </div>
                </div>

                {/* Meta: group + venue */}
                <div className="relative mt-3 font-mono text-[11px] text-center opacity-75 tracking-[.06em] flex justify-center items-center gap-1.5">
                    <span>{group ? `FASE GRUPOS · GRUPO ${group}` : 'ELIMINACIÓN DIRECTA'}</span>
                    {venue && (
                        <>
                            <span className="opacity-50">·</span>
                            <span>{venue}</span>
                        </>
                    )}
                </div>

                {/* My prediction strip */}
                {myPick && (
                    <div className="relative mt-3.5 bg-pop-yel text-ink border-[2.5px] border-ink shadow-pop flex justify-between items-center px-3 py-2">
                        <div>
                            <div className="font-mono text-[9px] tracking-[.1em] opacity-75">TUS GOLES</div>
                            <div className="font-display text-[22px] leading-none mt-0.5">
                                {myPick.replace('-', ' — ')}
                            </div>
                        </div>
                        <div className="text-right flex flex-col items-end gap-1">
                            {isLive && isWinnerCorrect && (
                                <span className="font-mono text-[9px] font-bold tracking-[.08em] bg-pop-teal text-white px-1.5 py-0.5 border border-ink">
                                    ✓ VAS X EL GANADOR
                                </span>
                            )}
                            {isLive && myPts != null && myPts > 0 && (
                                <div className="font-display text-[14px] text-pop-red">+{myPts} PTS</div>
                            )}
                            {!isLive && (
                                <>
                                    <div className="font-mono text-[10px] font-bold">POSIBLES</div>
                                    <div className="font-display text-[18px] text-pop-red">
                                        +{myPts ?? '?'} PTS
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
