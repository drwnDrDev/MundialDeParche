import { router } from '@inertiajs/react';
import { Whistle } from '@/Components/icons/football';

export default function DeadlineAlert({ deadlineAlert, onDismiss }) {
    const { round, hoursLeft, minutesLeft, pending, total, pendingSpecials } = deadlineAlert;
    const filled = total > 0 ? Math.round(((total - pending) / total) * 100) : 0;

    const pad = (n) => String(n).padStart(2, '0');

    return (
        <div className="fixed max-w-3xl inset-0 z-50 bg-pop-yel overflow-hidden flex flex-col pb-[79px]">

            {/* Halftone red overlay */}
            <div
                className="absolute inset-0 pointer-events-none"
                style={{
                    backgroundImage: 'radial-gradient(var(--c-red) 1.2px, transparent 1.6px)',
                    backgroundSize: '8px 8px',
                    opacity: 0.25,
                }}
            />

            {/* Diagonal stripes — top */}
            <div
                className="absolute top-0 left-0 right-0 h-[80px] pointer-events-none"
                style={{
                    background: 'repeating-linear-gradient(-45deg, var(--c-ink) 0 20px, transparent 20px 40px)',
                    opacity: 0.15,
                }}
            />

            {/* Diagonal stripes — bottom */}
            <div
                className="absolute bottom-0 left-0 right-0 h-[80px] pointer-events-none"
                style={{
                    background: 'repeating-linear-gradient(-45deg, var(--c-ink) 0 20px, transparent 20px 40px)',
                    opacity: 0.15,
                }}
            />

            {/* Whistle — top right */}
            <div className="absolute top-[100px] right-[-10px]" style={{ transform: 'rotate(18deg)' }}>
                <Whistle size={56} />
            </div>

            {/* Content */}
            <div className="relative z-10 flex-1 flex flex-col justify-center px-6 pt-10">
                <div
                    className="font-display text-[60px] leading-none text-pop-red"
                    style={{
                        WebkitTextStroke: '2.5px var(--c-ink)',
                        textShadow: '5px 5px 0 var(--c-ink)',
                    }}
                >
                    ¡PILAS,
                </div>
                <div
                    className="font-display text-[56px] leading-none text-cream mt-0.5"
                    style={{
                        WebkitTextStroke: '2.5px var(--c-ink)',
                        textShadow: '5px 5px 0 var(--c-red)',
                    }}
                >
                    PARCERO!
                </div>

                {/* Countdown */}
                <div className="mt-6 flex gap-1.5 justify-center">
                    {[
                        { v: pad(hoursLeft),   l: 'HORAS' },
                        { v: pad(minutesLeft), l: 'MIN' },
                        { v: '00',             l: 'SEG' },
                    ].map((u, i) => (
                        <div
                            key={i}
                            className="flex-1 text-center bg-ink text-pop-yel border-[3px] border-ink py-2.5 px-1"
                            style={{ boxShadow: '4px 4px 0 var(--c-red)' }}
                        >
                            <div className="font-display text-[36px] leading-none">{u.v}</div>
                            <div className="font-mono text-[9px] text-cream tracking-[.1em] mt-0.5">{u.l}</div>
                        </div>
                    ))}
                </div>

                {/* Missing card */}
                <div
                    className="mt-6 px-3.5 py-3 bg-white border-[3px] border-ink"
                    style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                >
                    <div className="flex justify-between items-baseline">
                        <div className="font-display text-[16px]">TE FALTAN</div>
                        <div className="font-display text-[36px] text-pop-red leading-none">{pending}</div>
                    </div>
                    <div className="font-mono text-[11px] tracking-[.06em] mt-1">
                        goles por meter en <b>{round.toUpperCase()}</b>
                    </div>
                    {/* Progress bar */}
                    <div className="mt-2.5 h-2 bg-cream border border-ink overflow-hidden">
                        <div
                            className="h-full bg-pop-red"
                            style={{ width: `${filled}%` }}
                        />
                    </div>
                    <div className="font-mono text-[9px] opacity-60 mt-1 text-right">{total - pending} / {total}</div>
                </div>

                {/* Especiales pendientes — solo R1 */}
                {pendingSpecials && (
                    <div
                        className="mt-3 px-3.5 py-2.5 bg-pop-yel/20 border-[2.5px] border-ink"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        <div className="flex items-center gap-2">
                            <span className="text-[16px]">⚠️</span>
                            <div>
                                <div className="font-display text-[13px] leading-tight">ESPECIALES SIN GUARDAR</div>
                                <div className="font-mono text-[10px] opacity-70 mt-0.5">Campeón · Sub-campeón · Goleador</div>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* CTAs */}
            <div className="relative z-10 flex-shrink-0 flex flex-col gap-2 px-6 pb-[30px]">
                <button
                    onClick={() => {
                        onDismiss();
                        router.visit(route('predictions.index'));
                    }}
                    className="w-full py-[18px] bg-pop-red text-white font-display text-[18px] tracking-[.01em] border-[2.5px] border-ink active:translate-x-[3px] active:translate-y-[3px]"
                    style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                >
                    TERMINAR YA →
                </button>
                <button
                    onClick={onDismiss}
                    className="w-full py-2 text-ink font-display text-[12px] tracking-[.01em] opacity-80"
                >
                    YA LO SÉ, CERRAR
                </button>
            </div>
        </div>
    );
}
