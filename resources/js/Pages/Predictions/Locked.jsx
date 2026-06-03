import { Head, Link } from '@inertiajs/react';

export default function Locked({ roundName, roundOrder, isLocked, opensAt }) {
    return (
        <>
            <Head title="Fase bloqueada" />
            <div className="bg-navy text-cream min-h-screen overflow-hidden">
            <div className="max-w-3xl mx-auto min-h-screen relative overflow-hidden flex flex-col">
                {/* Scanlines */}
                <div className="scanlines absolute inset-0 pointer-events-none" />

                {/* Halftone corner */}
                <div
                    className="halftone halftone-yel absolute top-0 right-0 w-[200px] h-[200px] pointer-events-none"
                    style={{ opacity: .2 }}
                />

                {/* Content */}
                <div className="flex-1 flex flex-col justify-center items-center px-7 text-center relative">

                    {/* Lock graphic */}
                    <div className="relative flex justify-center mb-3.5">
                        <div
                            className="halftone halftone-red absolute w-[200px] h-[200px] rounded-full"
                            style={{ opacity: .4 }}
                        />
                        <div
                            className="relative w-[140px] h-[140px] bg-navy border-[4px] border-pop-yel flex items-center justify-center"
                            style={{
                                transform: 'rotate(-4deg)',
                                boxShadow: '6px 6px 0 var(--c-red)',
                            }}
                        >
                            <svg width="80" height="80" viewBox="0 0 60 60" fill="none">
                                <rect x="10" y="26" width="40" height="28" fill="var(--c-yel)" stroke="var(--c-ink)" strokeWidth="3" />
                                <path
                                    d="M16 26 V18 C 16 10, 24 6, 30 6 C 36 6, 44 10, 44 18 V26"
                                    stroke="var(--c-yel)"
                                    strokeWidth="4"
                                    fill="none"
                                />
                                <rect x="26" y="36" width="8" height="12" fill="var(--c-ink)" />
                            </svg>
                        </div>
                    </div>

                    <div className="font-display text-[14px] text-pop-yel tracking-[.08em] mb-1">
                        ESPERÁ UN TOQUE —
                    </div>
                    <div
                        className="font-display text-[38px] leading-none text-cream"
                        style={{ textShadow: '3px 3px 0 var(--c-red)' }}
                    >
                        {isLocked ? (
                            <>FASE {roundOrder}<br />CERRADA</>
                        ) : (
                            <>FASE {roundOrder}<br />BLOQUEADA</>
                        )}
                    </div>

                    <p className="font-body text-[14px] text-cream opacity-85 leading-snug mt-4 max-w-[280px]">
                        {isLocked
                            ? `La ${roundName} ya está cerrada. Las predicciones son definitivas.`
                            : `Esta fase se abre cuando se cierre la fase anterior.`}
                    </p>

                    {/* Countdown */}
                    {opensAt && !isLocked && (
                        <div className="mt-5 px-3 py-2.5 border-2 border-dashed border-pop-yel" style={{ background: 'rgba(0,0,0,.4)' }}>
                            <div className="font-mono text-[10px] text-pop-yel tracking-[.08em]">SE ABRE EN</div>
                            <div className="font-display text-[24px] text-cream mt-0.5">{opensAt}</div>
                        </div>
                    )}
                </div>

                {/* CTAs */}
                <div className="flex-shrink-0 px-6 pb-8 flex flex-col gap-2">
                    <Link
                        href="/chat"
                        className="block w-full py-4 bg-pop-yel text-ink font-display text-[16px] text-center border-[2.5px] border-ink tracking-[.02em]"
                        style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        MIENTRAS, AL CHAT
                    </Link>
                    <Link
                        href="/ranking"
                        className="block w-full py-3 text-center font-mono text-[12px] text-cream opacity-80 underline"
                    >
                        VER RANKING
                    </Link>
                </div>
            </div>
            </div>
        </>
    );
}
