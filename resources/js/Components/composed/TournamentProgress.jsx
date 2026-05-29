const SLUG_LABELS = {
    'grupos': 'GRUPOS',
    'r32':    'R32',
    'f3':     '8VOS+4TOS',
    'f4':     'SEMIS+FINAL',
};

function nodeState(round, submission) {
    if (round.is_locked && submission) return 'finalized';
    if (round.is_open || round.is_locked)  return 'active';
    return 'upcoming';
}

export default function TournamentProgress({ rounds, submissions }) {
    return (
        <div className="flex items-start px-[18px] py-3">
            {rounds.map((round, i) => {
                const state      = nodeState(round, submissions[round.id]);
                const isLast     = i === rounds.length - 1;
                const isFirst    = i === 0;
                const isUpcoming = state === 'upcoming';

                const nodeClass = {
                    finalized: 'bg-ink border-ink',
                    active:    'bg-pop-red border-ink',
                    upcoming:  'bg-cream border-ink/30',
                }[state];

                return (
                    <div key={round.id} className="flex flex-col items-center flex-1">
                        <div className="flex items-center w-full">
                            {/* Línea izquierda */}
                            {!isFirst && (
                                <div className={`flex-1 h-[3px] ${state === 'finalized' ? 'bg-ink' : 'bg-ink/15'}`} />
                            )}

                            {/* Nodo */}
                            <div className={`w-7 h-7 rounded-full border-[2.5px] flex items-center justify-center flex-shrink-0 ${nodeClass}`}>
                                {state === 'finalized' && (
                                    <span className="text-cream font-mono text-[11px] font-bold">✓</span>
                                )}
                                {state === 'active' && (
                                    <span className="w-2 h-2 rounded-full bg-cream" />
                                )}
                                {state === 'upcoming' && (
                                    <span className="text-ink/30 text-[10px]">🔒</span>
                                )}
                            </div>

                            {/* Línea derecha */}
                            {!isLast && (
                                <div className={`flex-1 h-[3px] ${state === 'finalized' ? 'bg-ink' : 'bg-ink/15'}`} />
                            )}
                        </div>

                        <div className={`font-mono text-[7.5px] mt-1.5 tracking-[.04em] text-center leading-none ${isUpcoming ? 'opacity-30' : 'opacity-80'}`}>
                            {SLUG_LABELS[round.slug] ?? round.name.toUpperCase()}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
