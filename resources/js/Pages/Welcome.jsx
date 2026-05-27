import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { SoccerBall } from '@/Components/icons/football';
import Burst from '@/Components/ui/Burst';

function HostStrip() {
    return (
        <div className="flex items-center gap-3 font-mono text-[11px] tracking-[.06em] text-cream opacity-80">
            <span>🇺🇸 USA</span>
            <span className="opacity-50">·</span>
            <span>🇨🇦 CAN</span>
            <span className="opacity-50">·</span>
            <span>🇲🇽 MEX</span>
        </div>
    );
}

export default function Welcome() {
    return (
        <>
            <Head title="Bienvenido · Mundial de Parche" />
            <div className="bg-navy text-cream min-h-screen overflow-hidden relative flex flex-col">

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
                <div className="speedlines absolute inset-0 pointer-events-none" style={{ opacity: .18 }} />

                {/* Stamp — top center */}
                <div className="absolute top-[50px] left-0 right-0 flex justify-center z-10">
                    <div
                        className="bg-pop-red text-white border-[2.5px] border-ink px-3.5 py-1 font-pixel text-[18px] tracking-[.06em]"
                        style={{ transform: 'rotate(-2deg)', boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        ★ INVITACIÓN OFICIAL ★
                    </div>
                </div>

                {/* Burst — top right */}
                <div className="absolute top-[130px] right-3 z-10" style={{ transform: 'rotate(14deg)' }}>
                    <Burst color="yel" size="lg">
                        ¡DALE, PARCERO!
                    </Burst>
                </div>

                {/* Main content */}
                <div className="relative z-10 flex-1 flex flex-col justify-center text-center px-[26px] pt-10">
                    <div className="font-display text-[32px] leading-none text-cream">
                        HOLA,
                    </div>
                    <div
                        className="font-display text-[74px] leading-[.9] text-pop-yel mt-0.5"
                        style={{
                            WebkitTextStroke: '2.5px var(--c-ink)',
                            textShadow: '5px 5px 0 var(--c-ink)',
                        }}
                    >
                        PARCERO
                    </div>

                    {/* Divider */}
                    <div className="flex items-center gap-1.5 my-3.5">
                        <div className="flex-1 h-0.5 bg-pop-yel" />
                        <SoccerBall size={20} />
                        <div className="flex-1 h-0.5 bg-pop-yel" />
                    </div>

                    <div className="font-body text-[15px] leading-[1.4] text-cream">
                        Has sido <b className="text-pop-yel">elegido</b> para demostrar de qué estás hecho.
                    </div>

                    {/* Red welcome card */}
                    <div
                        className="mt-[18px] px-3.5 py-2.5 bg-pop-red text-white border-[2.5px] border-ink relative overflow-hidden"
                        style={{ transform: 'rotate(-1.5deg)', boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        <div
                            className="absolute inset-0 pointer-events-none"
                            style={{
                                backgroundImage: 'radial-gradient(var(--c-yel) 1.2px, transparent 1.6px)',
                                backgroundSize: '8px 8px',
                                opacity: 0.18,
                            }}
                        />
                        <div className="relative font-pixel text-[18px] tracking-[.02em]">BIENVENIDO AL</div>
                        <div className="relative font-display text-[20px] leading-none mt-1">
                            PARCHE DE LOS<br />DUROS DEL MUNDIAL
                        </div>
                    </div>
                </div>

                {/* WC26 logo */}
                <div className="relative z-10 flex justify-center mb-2.5">
                    <img src="/assets/wc26_logo.avif" alt="WC26" className="w-[70px] h-auto block" />
                </div>

                {/* Host strip */}
                <div className="relative z-10 flex justify-center mb-4">
                    <HostStrip />
                </div>

                {/* CTAs */}
                <div className="relative z-10 flex flex-col gap-2.5 px-[26px] pb-7 flex-shrink-0">
                    <button
                        onClick={() => router.visit(route('register'))}
                        className="w-full py-[18px] bg-pop-yel text-ink font-display text-[17px] tracking-[.01em] border-[2.5px] border-ink active:translate-x-[3px] active:translate-y-[3px]"
                        style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        ACEPTO EL RETO →
                    </button>
                    <button
                        onClick={() => router.visit(route('how-to-play'))}
                        className="w-full py-2 text-cream font-display text-[11px] tracking-[.01em] opacity-80"
                    >
                        ¿CÓMO SE JUEGA?
                    </button>
                </div>
            </div>
        </>
    );
}
