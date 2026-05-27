import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';

function Rule({ n, title, children }) {
    return (
        <div className="mb-[18px]">
            <div className="flex items-center gap-2 mb-1.5">
                <span
                    className="bg-pop-red text-white border-2 border-ink px-[7px] py-0.5 font-display text-[13px]"
                    style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                >
                    {n}
                </span>
                <div className="font-display text-[15px]">{title}</div>
                <div className="flex-1 h-0.5 bg-ink" />
            </div>
            <div className="font-body text-[13px] leading-[1.5] text-ink opacity-92">
                {children}
            </div>
        </div>
    );
}

function RuleList({ items, ordered = false }) {
    return (
        <ul className={`mt-2 mb-0 ${ordered ? 'pl-[18px] list-decimal' : 'pl-0 list-none'}`}>
            {items.map((text, i) => (
                <li key={i} className="font-mono text-[11px] font-semibold py-[3px] tracking-[.02em] relative" style={{ paddingLeft: ordered ? '4px' : '14px' }}>
                    {!ordered && (
                        <span className="absolute left-0 top-[6px] w-2 h-2 bg-pop-yel border-[1.5px] border-ink" />
                    )}
                    {text}
                </li>
            ))}
        </ul>
    );
}

export default function Rules() {
    return (
        <>
            <Head title="Reglas · Mundial de Parche" />
            <div className="bg-cream min-h-screen overflow-hidden relative flex flex-col">

                {/* Halftone navy — bottom left */}
                <div
                    className="absolute bottom-0 left-0 w-[200px] h-[200px] pointer-events-none"
                    style={{
                        backgroundImage: 'radial-gradient(var(--c-navy) 1.2px, transparent 1.6px)',
                        backgroundSize: '8px 8px',
                        opacity: 0.08,
                        WebkitMaskImage: 'radial-gradient(circle at 0% 100%, #000 40%, transparent 70%)',
                        maskImage: 'radial-gradient(circle at 0% 100%, #000 40%, transparent 70%)',
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
                        <div
                            className="bg-navy text-cream border-2 border-ink px-2 py-0.5 font-mono text-[9px] font-bold tracking-[.06em]"
                            style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                        >
                            v1.0 · 25 MAY 2026
                        </div>
                    </div>
                    <div className="flex items-end gap-2 mt-3">
                        <div className="font-display text-[40px] leading-[.9] text-ink">REGLAS</div>
                        <div className="font-pixel text-[20px] text-pop-red pb-1">★ FULL ★</div>
                    </div>
                    <div className="font-mono text-[11px] opacity-70 tracking-[.06em] mt-1">
                        Lo que toca saber para no tener líos
                    </div>
                </div>

                {/* Index strip */}
                <div className="flex-shrink-0 px-3.5 pt-3 pb-0">
                    <div className="flex gap-1 overflow-x-auto pb-1.5" style={{ WebkitOverflowScrolling: 'touch' }}>
                        {['INSCRIPCIÓN', 'FASES', 'PUNTOS', 'EMPATES', 'PREMIOS', 'CONDUCTA'].map((s, i) => (
                            <div
                                key={s}
                                className={`flex-shrink-0 px-2 py-1 border-2 border-ink font-mono text-[9px] font-bold tracking-[.06em] ${i === 0 ? 'bg-pop-yel text-ink' : 'bg-white text-ink'}`}
                                style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                            >
                                {i + 1}. {s}
                            </div>
                        ))}
                    </div>
                </div>

                {/* Scroll area */}
                <div className="flex-1 overflow-y-auto px-[18px] pt-3" style={{ WebkitOverflowScrolling: 'touch' }}>
                    <Rule n="1" title="INSCRIPCIÓN">
                        Para entrar al parche aportás <b>50K una sola vez</b>, antes del primer partido de la fase de grupos.
                        El admin verifica el pago y te activa la cuenta. Si no estás activado al cierre de la Fase 1, no podés participar.
                    </Rule>

                    <Rule n="2" title="FASES Y CIERRES">
                        El torneo tiene 4 fases. Cada una se cierra <b>antes del pitazo inicial del primer partido</b> de esa fase.
                        Después del cierre, los goles guardados quedan en piedra — no se cambian.
                        <RuleList items={[
                            'Fase 1 · Grupos · 72 partidos',
                            'Fase 2 · R32 + R16 · 24 partidos',
                            'Fase 3 · 8vos + 4tos · 12 partidos',
                            'Fase 4 · Semis + Final · 3 partidos',
                        ]} />
                    </Rule>

                    <Rule n="3" title="PUNTUACIÓN">
                        Sumás puntos por cada acierto, según lo que metiste:
                        <RuleList items={[
                            '+5 pts · marcador exacto (ej: si pusiste 2-1 y queda 2-1)',
                            '+2 pts · ganador correcto (si pusiste 2-1 y queda 3-0, igual sumás)',
                            '+3 pts · clasificado correcto a la siguiente ronda',
                        ]} />
                        <span className="inline-block mt-1.5 font-mono text-[10px] opacity-70 leading-[1.5]">
                            Los goles de tiempos extra y penales <b>no cuentan</b> para el marcador.
                        </span>
                    </Rule>

                    <Rule n="4" title="EMPATES DE PUNTAJE">
                        Si al final dos o más parceros quedan con los mismos puntos, se desempata así:
                        <RuleList ordered items={[
                            'Más marcadores exactos durante todo el torneo',
                            'Mejor predicción de la Final',
                            'Sorteo arbitrado por el admin (en último caso)',
                        ]} />
                    </Rule>

                    <Rule n="5" title="PREMIOS">
                        El pozo se forma con los 50K de cada parche que entra. Se reparte así al final:
                        <RuleList items={[
                            '1° lugar · 70% del pozo',
                            '2° lugar · 30% del pozo',
                        ]} />
                        <span className="inline-block mt-1.5 font-mono text-[10px] opacity-70 leading-[1.5]">
                            El admin coordina el pago dentro de los 7 días siguientes a la final. Cero comisión.
                        </span>
                    </Rule>

                    <Rule n="6" title="CONDUCTA Y CHAT">
                        El chat es para alentar, picar al rival y celebrar — no para insultos ni faltas de respeto.
                        El admin puede silenciar o eliminar a un parcero que se pase de la raya. Cero reembolso en ese caso.
                    </Rule>

                    <Rule n="7" title="LO IMPREVISTO">
                        Si la FIFA cambia un partido, una sede, o suspende algo, el admin <b>ajusta el calendario</b> dentro de la app.
                        Los goles ya guardados se mantienen como estaban.
                    </Rule>

                    {/* Admin card */}
                    <div className="mt-[18px] mb-3.5 px-3 py-2.5 bg-navy text-cream border-[2.5px] border-ink flex items-center gap-2.5">
                        <div className="text-[18px]">📞</div>
                        <div className="flex-1 font-mono text-[10px] leading-[1.5] tracking-[.02em]">
                            ¿Dudas? Hablale al admin <b className="text-pop-yel">Edisson Á.</b> por WhatsApp.
                        </div>
                    </div>

                    <div className="pb-5 text-center font-pixel text-[14px] opacity-45">
                        ★ MUNDIAL DE PARCHE · v1.0 ★
                    </div>
                </div>

                {/* Sticky CTAs */}
                <div className="flex-shrink-0 flex gap-2 px-[18px] py-2.5 pb-[22px] bg-cream border-t-[3px] border-ink">
                    <button
                        onClick={() => {
                            if (navigator.share) {
                                navigator.share({ title: 'Mundial de Parche · Reglas', url: window.location.href });
                            } else {
                                navigator.clipboard.writeText(window.location.href);
                            }
                        }}
                        className="flex-1 py-2.5 bg-white text-ink font-display text-[12px] border-[2.5px] border-ink active:translate-x-[3px] active:translate-y-[3px]"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        COMPARTIR
                    </button>
                    <button
                        onClick={() => router.visit(route('home'))}
                        className="flex-[2] py-2.5 bg-pop-red text-white font-display text-[13px] border-[2.5px] border-ink active:translate-x-[3px] active:translate-y-[3px]"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        VOLVER AL PARCHE →
                    </button>
                </div>
            </div>
        </>
    );
}
