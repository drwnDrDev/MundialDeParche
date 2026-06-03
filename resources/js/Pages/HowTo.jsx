import { Head, router } from '@inertiajs/react';
import { Trophy, SoccerBall } from '@/Components/icons/football';
import Cromo from '@/Components/ui/Cromo';

function SectionHead({ title, accent = 'red' }) {
    return (
        <div className="flex items-center gap-2 py-2.5">
            <span className={`w-3.5 h-3.5 flex-shrink-0 bg-pop-${accent} border-2 border-ink`} />
            <div className="font-display text-[14px] tracking-[.02em]">{title}</div>
            <div className="flex-1 h-[3px] bg-ink" />
        </div>
    );
}

function KIcon() {
    return (
        <div
            className="w-8 h-8 rounded-full bg-pop-yel text-ink border-[2.5px] border-ink flex items-center justify-center font-display text-[16px]"
            style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
        >
            K
        </div>
    );
}

function Step({ n, color, title, copy, icon }) {
    return (
        <div
            className="flex items-stretch gap-2.5 mb-3 bg-white border-[2.5px] border-ink p-2.5 relative overflow-hidden"
            style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
        >
            <div
                className="w-[60px] flex-shrink-0 border-[2.5px] border-ink flex flex-col items-center justify-center relative"
                style={{ background: color }}
            >
                <div
                    className="absolute inset-0 pointer-events-none"
                    style={{
                        backgroundImage: 'radial-gradient(rgba(0,0,0,.9) 1.2px, transparent 1.6px)',
                        backgroundSize: '8px 8px',
                        opacity: 0.08,
                    }}
                />
                <div className="font-display text-[36px] text-ink leading-none relative">{n}</div>
                <div className="mt-1 relative">{icon}</div>
            </div>
            <div className="flex-1 min-w-0 flex flex-col justify-center">
                <div className="font-display text-[16px] leading-tight">{title}</div>
                <div className="font-body text-[13px] mt-1.5 leading-[1.4] opacity-85">{copy}</div>
            </div>
        </div>
    );
}

function ScoreLine({ pts, label, sub, color, dark = false }) {
    return (
        <div
            className="flex items-center gap-2.5 px-3 py-2.5 bg-white border-[2.5px] border-ink"
            style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
        >
            <div
                className={`flex-shrink-0 min-w-[50px] text-center border-2 border-ink px-2.5 py-1.5 font-display text-[18px] ${dark ? 'text-ink' : 'text-white'}`}
                style={{ background: color }}
            >
                {pts}
            </div>
            <div className="flex-1 min-w-0">
                <div className="font-display text-[12px] leading-none">{label}</div>
                <div className="font-mono text-[10px] opacity-70 mt-1">{sub}</div>
            </div>
        </div>
    );
}

function PrizeBlock({ place, pct }) {
    return (
        <div className="flex-1 p-2 text-center border-2 border-pop-yel" style={{ background: 'rgba(0,0,0,.3)' }}>
            <div className="font-display text-[20px] text-pop-yel">{place}</div>
            <div className="font-mono font-bold text-[14px] text-cream mt-0.5 tracking-[.04em]">{pct}</div>
            <div className="font-mono text-[9px] opacity-65 mt-0.5 tracking-[.06em]">DEL POZO</div>
        </div>
    );
}

export default function HowTo() {
    return (
        <>
            <Head title="Cómo se juega · Mundial de Parche" />
            <div className="bg-cream min-h-screen overflow-hidden">
            <div className="max-w-3xl mx-auto min-h-screen relative overflow-hidden flex flex-col">

                {/* Halftone yel — top right */}
                <div
                    className="absolute top-0 right-0 w-[200px] h-[200px] pointer-events-none"
                    style={{
                        backgroundImage: 'radial-gradient(var(--c-yel) 1.2px, transparent 1.6px)',
                        backgroundSize: '8px 8px',
                        opacity: 0.22,
                        WebkitMaskImage: 'radial-gradient(circle at 100% 0%, #000 40%, transparent 70%)',
                        maskImage: 'radial-gradient(circle at 100% 0%, #000 40%, transparent 70%)',
                    }}
                />

                {/* Header */}
                <div className="relative px-[18px] pt-3 pb-0 flex-shrink-0">
                    <div className="flex items-center justify-between">
                        <button
                            onClick={() => window.history.back()}
                            className="w-8 h-8 border-2 border-ink flex items-center justify-center font-display text-[14px]"
                        >
                            ←
                        </button>
                        <div className="bg-pop-yel text-ink border-2 border-ink px-2 py-0.5 font-mono text-[9px] font-bold tracking-[.06em]" style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}>
                            EN 3 PASOS
                        </div>
                    </div>
                    <div className="font-display text-[30px] leading-none mt-3.5">ASÍ FUNCIONA</div>
                    <div
                        className="font-display text-[44px] leading-[.9] mt-0.5 text-pop-red"
                        style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}
                    >
                        EL PARCHE
                    </div>
                </div>

                {/* Scroll area */}
                <div className="flex-1 overflow-y-auto px-[18px] pt-4 pb-2" style={{ WebkitOverflowScrolling: 'touch' }}>
                    <Step
                        n="1"
                        color="var(--c-red)"
                        title="ENTRÁ AL POZO"
                        copy="Aportas 50K una sola vez. El admin te activa y quedas dentro."
                        icon={<KIcon />}
                    />
                    <Step
                        n="2"
                        color="var(--c-teal)"
                        title="METÉ TUS GOLES"
                        copy="En cada fase predices los marcadores de todos los partidos. Tienes tiempo hasta el cierre de la fase."
                        icon={<SoccerBall size={38} />}
                    />
                    <Step
                        n="3"
                        color="var(--c-yel)"
                        title="SUMÁ PUNTOS"
                        copy="Por cada acierto te caen puntos. El que más sume al final, se lleva el pozo."
                        icon={<Trophy size={36} />}
                    />

                    <SectionHead title="LA PUNTUACIÓN" accent="red" />
                    <div
                        className="bg-white border-[2.5px] border-ink overflow-hidden"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        {/* Header de columnas */}
                        <div className="grid grid-cols-4 border-b-[2px] border-ink bg-ink text-cream">
                            <div className="col-span-1 px-2 py-1.5 font-mono text-[8px] tracking-[.06em]">FASE</div>
                            <div className="px-2 py-1.5 font-mono text-[8px] tracking-[.06em] text-center text-pop-red">EXACTO</div>
                            <div className="px-2 py-1.5 font-mono text-[8px] tracking-[.06em] text-center text-pop-teal">GANADOR</div>
                            <div className="px-2 py-1.5 font-mono text-[8px] tracking-[.06em] text-center text-pop-yel">CLASIF</div>
                        </div>
                        {[
                            { fase: 'Grupos',    exacto: 3,  ganador: 1, clasif: 2 },
                            { fase: 'R32',  exacto: 5,  ganador: 2, clasif: 3 },
                            { fase: 'R16+8vos',  exacto: 8,  ganador: 3, clasif: 5 },
                            { fase: 'SF+Final',    exacto: 13, ganador: 5, clasif: null },
                        ].map(({ fase, exacto, ganador, clasif }, i) => (
                            <div key={fase} className={`grid grid-cols-4 ${i < 3 ? 'border-b border-ink/15' : ''}`}>
                                <div className="col-span-1 px-2 py-2 font-mono text-[9px] font-bold opacity-70">{fase}</div>
                                <div className="px-2 py-2 font-display text-[14px] text-center text-pop-red">+{exacto}</div>
                                <div className="px-2 py-2 font-display text-[14px] text-center text-pop-teal">+{ganador}</div>
                                <div className="px-2 py-2 font-display text-[14px] text-center text-pop-yel">
                                    {clasif ? `+${clasif}` : <span className="text-ink/20 text-[12px]">—</span>}
                                </div>
                            </div>
                        ))}
                    </div>
                    <div className="font-mono text-[10px] opacity-60 mt-2 leading-[1.4]">
                        Los puntos aumentan cada fase — vale más acertar en la final.
                    </div>

                    <SectionHead title="EL POZO" accent="teal" />
                    <Cromo className="bg-navy text-cream p-3.5">
                        <div
                            className="absolute inset-0 pointer-events-none"
                            style={{
                                backgroundImage: 'radial-gradient(var(--c-yel) 1.2px, transparent 1.6px)',
                                backgroundSize: '8px 8px',
                                opacity: 0.22,
                            }}
                        />
                        <div className="absolute right-[-8px] bottom-[-10px]" style={{ transform: 'rotate(-8deg)' }}>
                            <Trophy size={56} color="var(--c-yel)" />
                        </div>
                        <div className="relative">
                            <div className="font-mono text-[10px] tracking-[.1em] text-pop-yel">SE REPARTE EN</div>
                            <div className="flex gap-2 mt-2">
                                <PrizeBlock place="1°" pct="70%" />
                                <PrizeBlock place="2°" pct="20%" />
                            </div>
                        </div>
                    </Cromo>

                    {/* Rules teaser */}
                    <div
                        className="mt-4 px-3 py-2.5 bg-white border-[2.5px] border-dashed border-ink flex items-center gap-2.5"
                        onClick={() => router.visit(route('rules'))}
                    >
                        <div className="font-display text-[22px]">📖</div>
                        <div className="flex-1 font-mono text-[11px] leading-[1.4]">
                            ¿Querés los detalles? <b><u>Mirá las reglas completas →</u></b>
                        </div>
                    </div>

                    <div className="pb-3.5 mt-2 text-center font-pixel text-[16px] text-ink opacity-50">
                        ★ JUEGA LIMPIO ★
                    </div>
                </div>

                {/* Sticky CTA */}
                <div className="flex-shrink-0 px-[18px] py-2.5 pb-[22px] bg-cream border-t-[3px] border-ink">
                    <button
                        onClick={() => router.visit(route('register'))}
                        className="w-full py-[18px] bg-pop-red text-white font-display text-[16px] tracking-[.01em] border-[2.5px] border-ink active:translate-x-[3px] active:translate-y-[3px]"
                        style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        ENTENDÍ, A METER GOLES →
                    </button>
                </div>
            </div>
            </div>
        </>
    );
}
