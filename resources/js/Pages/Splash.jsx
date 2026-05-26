import { Head, Link } from '@inertiajs/react';
import { Trophy, SoccerBall } from '@/Components/icons/football';

export default function Splash() {
    return (
        <>
            <Head title="Mundial de Parche" />
            <div className="bg-navy text-cream min-h-screen overflow-hidden relative">

                {/* Halftone cream overlay */}
                <div
                    className="absolute inset-0 pointer-events-none"
                    style={{
                        backgroundImage: 'radial-gradient(var(--c-cream) 1.4px, transparent 1.8px)',
                        backgroundSize: '8px 8px',
                        opacity: 0.08,
                    }}
                />

                {/* Speedlines */}
                <div className="speedlines absolute inset-0 pointer-events-none" style={{ opacity: .22 }} />

                {/* FIFA cover circle */}
                <div className="absolute top-[70px] left-1/2 -translate-x-1/2">
                    <div
                        className="w-[360px] h-[360px] rounded-full border-[5px] border-ink overflow-hidden bg-cover bg-center"
                        style={{
                            backgroundImage: "url('/assets/fifa_cover.png')",
                            boxShadow: '8px 8px 0 var(--c-ink)',
                        }}
                    />
                </div>

                {/* Trophy top-left */}
                <div className="absolute top-[84px] left-[18px]" style={{ transform: 'rotate(-10deg)' }}>
                    <Trophy size={56} />
                </div>

                {/* ¡GOOOL! burst top-right */}
                <div
                    className="absolute top-[78px] right-[14px] bg-pop-teal border-[2.5px] border-ink px-3 py-2 font-display text-[16px] text-ink"
                    style={{ transform: 'rotate(12deg)', boxShadow: '3px 3px 0 var(--c-ink)' }}
                >
                    ¡GOOOL!
                </div>

                {/* Main title */}
                <div className="absolute top-[390px] left-0 right-0 text-center px-7">
                    <div
                        className="font-display text-[30px] leading-none text-cream"
                        style={{ textShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        MUNDIAL DE
                    </div>
                    <div
                        className="font-display text-[68px] leading-none mt-0.5 text-pop-yel"
                        style={{
                            WebkitTextStroke: '2.5px var(--c-ink)',
                            textShadow: '5px 5px 0 var(--c-ink)',
                        }}
                    >
                        PARCHE
                    </div>
                    <div className="font-pixel text-[20px] text-cream tracking-[.05em] mt-3">
                        ★ EL JUEGO DEL MUNDIAL ★
                    </div>
                </div>

                {/* Host strip */}
                <div className="absolute bottom-[188px] left-0 right-0 flex justify-center">
                    <div className="flex items-center gap-3 font-mono text-[11px] tracking-[.06em] text-cream opacity-80">
                        <span>🇺🇸 USA</span>
                        <span className="opacity-50">·</span>
                        <span>🇨🇦 CAN</span>
                        <span className="opacity-50">·</span>
                        <span>🇲🇽 MEX</span>
                    </div>
                </div>

                {/* CTAs */}
                <div className="absolute bottom-[90px] left-6 right-6 flex flex-col gap-3">
                    <Link
                        href="/login"
                        className="block w-full py-4 bg-pop-yel text-ink font-display text-[18px] text-center border-[2.5px] border-ink tracking-[.02em]"
                        style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        ENTRÁ AL PARCHE →
                    </Link>
                    <Link href="/login" className="text-center font-mono text-[12px] text-cream opacity-85">
                        ¿Ya estás dentro? <u>Iniciá sesión</u>
                    </Link>
                </div>

                {/* Accents */}
                <div className="absolute bottom-7 left-3 opacity-70">
                    <SoccerBall size={36} />
                </div>
                <div
                    className="absolute bottom-9 right-3.5 bg-pop-yel text-ink border-[2px] border-ink font-mono text-[10px] px-2 py-0.5"
                    style={{ transform: 'rotate(-12deg)' }}
                >
                    v1.0 · BETA
                </div>
            </div>
        </>
    );
}
