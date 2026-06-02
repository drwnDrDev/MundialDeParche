import { Link } from '@inertiajs/react';

function deriveState(round, submission) {
    if (round.is_finalized && !submission)   return 'finalized_no_bet';
    if (round.is_finalized)                  return 'finalized';
    if (round.is_locked)                     return 'locked';
    if (!round.is_open)                      return 'upcoming';
    if (!submission)                         return 'open';
    if (submission.status === 'draft')       return 'draft';
    return 'submitted';
}

function ProgressBar({ value, max }) {
    const pct = max > 0 ? Math.min((value / max) * 100, 100) : 0;
    return (
        <div className="h-[5px] bg-black/15 border border-ink/20 mt-2">
            <div className="h-full bg-pop-teal transition-all" style={{ width: `${pct}%` }} />
        </div>
    );
}

export default function PhaseCard({ round, submission, phasePts }) {
    const state        = deriveState(round, submission);
    const fixtureCount = round.fixtures_count ?? 0;
    const predCount    = phasePts?.prediction_count ?? 0;

    const wrapperBase = 'border-[2.5px] border-ink p-3.5 relative overflow-hidden';

    // ── upcoming ──────────────────────────────────────────────────────────────
    if (state === 'upcoming') {
        return (
            <div className={`${wrapperBase} bg-cream opacity-50`}
                 style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}>
                <div className="flex items-center justify-between">
                    <div>
                        <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                        <div className="font-mono text-[10px] opacity-60 mt-0.5">{fixtureCount} partidos</div>
                    </div>
                    <span className="text-[20px]">🔒</span>
                </div>
            </div>
        );
    }

    // ── open ─────────────────────────────────────────────────────────────────
    if (state === 'open') {
        return (
            <div className={`${wrapperBase} bg-cream`}
                 style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}>
                <div className="flex items-center justify-between mb-2">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-pop-teal border border-ink" />
                            <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                        </div>
                        <div className="font-mono text-[10px] opacity-60 mt-0.5 ml-[18px]">{fixtureCount} partidos · abierta</div>
                    </div>
                </div>
                <Link
                    href={route('predictions.show', round.slug)}
                    className="block w-full py-2.5 bg-pop-red text-white font-display text-[13px] tracking-[.01em] border-[2px] border-ink text-center"
                    style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                >
                    METER GOLES →
                </Link>
            </div>
        );
    }

    // ── draft ─────────────────────────────────────────────────────────────────
    if (state === 'draft') {
        return (
            <div className={`${wrapperBase} bg-cream`}
                 style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}>
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-pop-yel border border-ink" />
                            <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                        </div>
                        <div className="font-mono text-[10px] opacity-60 mt-0.5 ml-[18px]">
                            {predCount} / {fixtureCount} goles metidos
                        </div>
                    </div>
                </div>
                <ProgressBar value={predCount} max={fixtureCount} />
                <Link
                    href={route('predictions.show', round.slug)}
                    className="block w-full py-2.5 mt-3 bg-pop-yel text-ink font-display text-[13px] tracking-[.01em] border-[2px] border-ink text-center"
                    style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                >
                    CONTINUAR →
                </Link>
                <Link
                    href={route('predictions.receipt', round.slug)}
                    className="block w-full py-1.5 mt-1.5 text-ink/50 font-mono text-[10px] tracking-[.04em] text-center underline"
                >
                    ver borrador guardado →
                </Link>
            </div>
        );
    }

    // ── submitted ─────────────────────────────────────────────────────────────
    if (state === 'submitted') {
        return (
            <div className={`${wrapperBase} bg-cream`}
                 style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}>
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="w-2.5 h-2.5 rounded-full bg-pop-teal border border-ink" />
                            <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                        </div>
                        <div className="font-mono text-[10px] opacity-60 mt-0.5 ml-[18px]">{fixtureCount} goles confirmados</div>
                    </div>
                    <span className="bg-pop-teal text-ink border border-ink px-2 py-0.5 font-mono text-[8px] font-bold tracking-[.06em]">
                        CONFIRMADA
                    </span>
                </div>
                <Link
                    href={route('predictions.receipt', round.slug)}
                    className="block w-full py-2 mt-3 bg-white text-ink font-display text-[12px] tracking-[.01em] border-[2px] border-ink text-center"
                    style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                >
                    VER COMPROBANTE →
                </Link>
            </div>
        );
    }

    // ── locked ────────────────────────────────────────────────────────────────
    if (state === 'locked') {
        return (
            <div className={`${wrapperBase} bg-cream`}
                 style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}>
                <div className="flex items-center justify-between">
                    <div>
                        <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                        <div className="font-mono text-[10px] opacity-60 mt-0.5">Fase cerrada</div>
                    </div>
                    <span className="bg-pop-red text-white border border-ink px-2 py-0.5 font-mono text-[8px] font-bold tracking-[.06em] animate-pulse">
                        EN JUEGO
                    </span>
                </div>
                <Link
                    href={route('predictions.receipt', round.slug)}
                    className="block w-full py-2 mt-3 bg-white text-ink font-display text-[12px] tracking-[.01em] border-[2px] border-ink text-center"
                    style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                >
                    VER COMPROBANTE →
                </Link>
            </div>
        );
    }

    // ── finalized_no_bet ──────────────────────────────────────────────────────
    if (state === 'finalized_no_bet') {
        return (
            <div className={`${wrapperBase} bg-navy text-cream opacity-70`}
                 style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}>
                <div className="flex items-center justify-between">
                    <div>
                        <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                        <div className="font-mono text-[10px] opacity-60 mt-0.5">No apostaste en esta fase</div>
                    </div>
                    <div className="bg-ink/30 text-cream border-[2px] border-cream/20 px-2.5 py-1 font-display text-[18px] flex-shrink-0">
                        +0
                    </div>
                </div>
            </div>
        );
    }

    // ── finalized ─────────────────────────────────────────────────────────────
    const total = phasePts?.total ?? 0;
    return (
        <div className={`${wrapperBase} bg-navy text-cream`}
             style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}>
            <div className="flex items-center justify-between">
                <div>
                    <div className="font-display text-[15px] leading-tight">{round.name.toUpperCase()}</div>
                    <div className="flex gap-2 mt-1.5 text-[9px] font-mono opacity-70">
                        {phasePts?.pts_exact    > 0 && <span>exacto +{phasePts.pts_exact}</span>}
                        {phasePts?.pts_result   > 0 && <span>result +{phasePts.pts_result}</span>}
                        {phasePts?.pts_classifier > 0 && <span>clasif +{phasePts.pts_classifier}</span>}
                        {total === 0 && <span className="opacity-50">sin puntos</span>}
                    </div>
                </div>
                <div className="bg-pop-yel text-ink border-[2px] border-ink px-2.5 py-1 font-display text-[18px] flex-shrink-0"
                     style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}>
                    +{total}
                </div>
            </div>
            <Link
                href={route('predictions.receipt', round.slug)}
                className="block w-full py-2 mt-3 bg-white/10 text-cream font-display text-[12px] tracking-[.01em] border-[2px] border-cream/30 text-center hover:bg-white/20"
            >
                VER COMPROBANTE →
            </Link>
        </div>
    );
}
