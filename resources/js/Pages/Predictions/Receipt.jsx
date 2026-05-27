import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import MobileShell from '@/Components/MobileShell';
import ReceiptMatchRow from '@/Components/composed/ReceiptMatchRow';

export default function Receipt({ round, fixtures, predictions, submission, isFinalized }) {
    const ptsExact      = Object.values(predictions).reduce((s, p) => s + (p.pts_exact  ?? 0), 0);
    const ptsResult     = Object.values(predictions).reduce((s, p) => s + (p.pts_result ?? 0), 0);
    const ptsClassifier = submission.pts_classifier ?? 0;
    const totalPts      = ptsExact + ptsResult + ptsClassifier;

    const isGroupsOrR2 = round.slug === 'grupos' || round.slug === 'r32-r16';

    return (
        <MobileShell>
            <Head title={`Comprobante · ${round.name}`} />

            {/* Header */}
            <div className="px-[18px] pt-3 pb-2.5 flex items-center gap-3 border-b-[3px] border-ink bg-cream sticky top-0 z-10">
                <button
                    onClick={() => router.visit(route('predictions.index'))}
                    className="w-8 h-8 border-[2.5px] border-ink flex items-center justify-center font-display text-[14px] flex-shrink-0"
                    style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                >
                    ←
                </button>
                <div className="flex-1 min-w-0">
                    <div className="font-mono text-[9px] opacity-50 tracking-[.06em]">COMPROBANTE</div>
                    <div className="font-display text-[18px] leading-tight truncate">{round.name.toUpperCase()}</div>
                </div>
                {isFinalized && (
                    <span
                        className="flex-shrink-0 bg-pop-teal text-ink border-[2px] border-ink px-2 py-0.5 font-mono text-[8px] font-bold tracking-[.06em]"
                        style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                    >
                        FINALIZADA
                    </span>
                )}
            </div>

            {/* Banner de puntos o aviso */}
            {isFinalized ? (
                <div className="bg-navy text-cream px-[18px] py-3 flex items-center justify-between border-b-[3px] border-ink">
                    <div className="flex flex-col gap-1 font-mono text-[10px]">
                        {ptsExact      > 0 && <span>EXACTO   <b className="text-pop-red">  +{ptsExact}</b></span>}
                        {ptsResult     > 0 && <span>RESULTADO <b className="text-pop-teal">+{ptsResult}</b></span>}
                        {ptsClassifier > 0 && <span>CLASIF    <b className="text-pop-yel"> +{ptsClassifier}</b></span>}
                        {totalPts === 0    && <span className="opacity-50">Sin puntos esta fase</span>}
                    </div>
                    <div
                        className="bg-pop-yel text-ink border-[2.5px] border-ink px-3 py-1.5 font-display text-[26px] leading-none flex-shrink-0"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        +{totalPts}
                    </div>
                </div>
            ) : (
                <div className="bg-pop-yel text-ink border-b-[3px] border-ink px-[18px] py-2.5 flex items-center gap-2.5">
                    <span className="text-[18px]">⏳</span>
                    <span className="font-mono text-[10px] leading-[1.4]">
                        Los puntos se calculan cuando finalicen los partidos
                    </span>
                </div>
            )}

            {/* Lista de partidos */}
            <div className="overflow-y-auto">
                {fixtures.map(fixture => (
                    <ReceiptMatchRow
                        key={fixture.id}
                        fixture={fixture}
                        prediction={predictions[fixture.id] ?? null}
                        isFinalized={isFinalized}
                    />
                ))}

                {/* Bloque de clasificados (R1 y R2 finalizadas) */}
                {isFinalized && isGroupsOrR2 && ptsClassifier > 0 && (
                    <div
                        className="mx-[18px] my-3 px-3.5 py-3 bg-ink text-cream border-[2.5px] border-ink"
                        style={{ boxShadow: '3px 3px 0 var(--c-yel)' }}
                    >
                        <div className="font-mono text-[9px] tracking-[.08em] opacity-60">CLASIFICADOS</div>
                        <div className="font-display text-[20px] mt-0.5">+{ptsClassifier} PTS</div>
                        <div className="font-mono text-[9px] opacity-50 mt-0.5">
                            Equipos que predijiste que avanzaban
                        </div>
                    </div>
                )}

                <div className="pb-10" />
            </div>
        </MobileShell>
    );
}
