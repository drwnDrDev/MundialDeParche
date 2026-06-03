import { router } from '@inertiajs/react';
import { Trophy, SoccerBall } from '@/Components/icons/football';
import Burst from '@/Components/ui/Burst';

export default function PhaseOpenAlert({ phaseAlert, onDismiss }) {
    const { fromRound, toRound, closeDate } = phaseAlert;
    const hasSubtitle = toRound.includes('+');

    return (
        <div className="fixed max-w-3xl inset-0 z-50 bg-pop-red text-cream overflow-hidden flex flex-col pb-[79px]">

            {/* Halftone overlay */}
            <div
                className="absolute inset-0 pointer-events-none"
                style={{
                    backgroundImage: 'radial-gradient(rgba(0,0,0,.9) 1.2px, transparent 1.6px)',
                    backgroundSize: '8px 8px',
                    opacity: 0.28,
                }}
            />

            {/* Speedlines */}
            <div className="speedlines absolute inset-0 pointer-events-none" style={{ opacity: .22 }} />

            {/* Burst — top right */}
            <div className="absolute top-[96px] right-[-30px]" style={{ transform: 'rotate(14deg)' }}>
                <Burst color="yel" size="xl">
                    ¡NUEVA FASE!
                </Burst>
            </div>

            {/* Trophy — top left */}
            <div className="absolute top-[110px] left-[22px]" style={{ transform: 'rotate(-10deg)' }}>
                <Trophy size={56} />
            </div>

            {/* Content */}
            <div className="relative z-10 flex-1 flex flex-col justify-center px-7 pt-[60px]">

                {/* fromRound chip */}
                <div className="inline-flex items-center gap-2 self-start border-[2.5px] border-ink px-3 py-1.5 font-mono text-[11px] font-bold tracking-[.1em]" style={{ background: 'rgba(0,0,0,.35)' }}>
                    {fromRound.toUpperCase()}
                    <span className="bg-ink text-pop-yel px-1.5 font-display text-[10px]">CERRADA ✓</span>
                </div>

                {/* Arrow */}
                <div className="ml-3.5 my-3.5 font-display text-[32px] text-pop-yel leading-none">↓</div>

                {/* New round */}
                <div className="font-display text-[14px] text-pop-yel tracking-[.06em] mb-1.5">ABRIÓ ▶</div>
                <div
                    className="font-display text-[48px] leading-none text-cream"
                    style={{ textShadow: '4px 4px 0 var(--c-ink)' }}
                >
                    {hasSubtitle ? 'NUEVA FASE' : toRound.toUpperCase()}
                </div>
                {hasSubtitle && (
                    <div
                        className="font-display text-[30px] leading-[.95] text-pop-yel mt-1"
                        style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}
                    >
                        {toRound.toUpperCase()}
                    </div>
                )}

                {/* Info card */}
                <div className="mt-[18px] p-3 bg-navy border-[2.5px] border-ink" style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}>
                    <div className="font-mono text-[10px] text-pop-yel tracking-[.1em]">QUÉ TENÉS QUE HACER</div>
                    <div className="font-body text-[13px] mt-1 leading-[1.35]">
                        Metele los goles a los partidos de <b className="text-pop-yel">{toRound}</b> antes del cierre.
                    </div>
                    <div className="mt-2 flex justify-between font-mono text-[10px] tracking-[.06em]">
                        <span className="opacity-70">CIERRE:</span>
                        <b className="text-pop-yel">{closeDate}</b>
                    </div>
                </div>
            </div>

            {/* CTA */}
            <div className="relative z-10 flex-shrink-0 px-6 pb-[30px]">
                <button
                    onClick={() => {
                        onDismiss();
                        router.visit(route('predictions.index'));
                    }}
                    className="w-full py-[18px] bg-pop-yel text-ink font-display text-[18px] tracking-[.01em] border-[2.5px] border-ink active:translate-x-[3px] active:translate-y-[3px]"
                    style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                >
                    ARRANCAR A METER GOLES →
                </button>
                <div className="text-center mt-2.5 font-mono text-[11px] text-cream opacity-85">
                    <u>Después, no se puede.</u>
                </div>
            </div>

            {/* Corner ball */}
            <div className="absolute bottom-[90px] right-3.5 opacity-85">
                <SoccerBall size={48} />
            </div>
        </div>
    );
}
