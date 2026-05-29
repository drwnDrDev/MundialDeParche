import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import MobileShell from '@/Components/MobileShell';
import ReceiptMatchRow from '@/Components/composed/ReceiptMatchRow';

export default function Receipt({ round, fixtures, predictions, submission, isFinalized, classifiers, realClassifierIds }) {
    const ptsExact      = Object.values(predictions).reduce((s, p) => s + (p.pts_exact  ?? 0), 0);
    const ptsResult     = Object.values(predictions).reduce((s, p) => s + (p.pts_result ?? 0), 0);
    const ptsClassifier = submission.pts_classifier ?? 0;
    const totalPts      = ptsExact + ptsResult + ptsClassifier;

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

                {/* Bloque de clasificados — predicted classifiers with hit/miss marks */}
                {classifiers && classifiers.length > 0 && (() => {
                    const realIds = new Set(realClassifierIds ?? []);

                    // Agrupar por grupo (solo posiciones 1 y 2)
                    const byGroup = {};
                    classifiers.forEach(c => {
                        if (!byGroup[c.group]) byGroup[c.group] = [];
                        byGroup[c.group].push(c);
                    });
                    const bestThirds = classifiers.filter(c => c.position === 3);

                    const hitCount = isFinalized
                        ? classifiers.filter(c => realIds.has(c.team_id)).length
                        : null;

                    return (
                        <div className="mx-[18px] my-3 border-[2.5px] border-ink overflow-hidden"
                             style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}>
                            {/* Header */}
                            <div className="bg-navy text-cream px-3.5 py-2.5 flex items-center justify-between">
                                <div>
                                    <div className="font-mono text-[8px] tracking-[.1em] opacity-70">FASE DE GRUPOS</div>
                                    <div className="font-display text-[15px] leading-none mt-0.5">TUS 32 CLASIFICADOS</div>
                                </div>
                                {isFinalized && (
                                    <div className="font-mono text-[8px] opacity-70 text-right leading-[1.4]">
                                        <div className="text-pop-teal font-bold">{hitCount} acertados</div>
                                        <div className="opacity-60">de {classifiers.length}</div>
                                    </div>
                                )}
                            </div>

                            {/* Grid por grupo — 1° y 2° */}
                            <div className="bg-white px-3 pt-2 pb-1">
                                <div className="grid grid-cols-2 gap-x-3 gap-y-0.5 mb-2">
                                    {Object.entries(byGroup)
                                        .sort(([a], [b]) => a.localeCompare(b))
                                        .flatMap(([groupName, entries]) =>
                                            entries
                                                .filter(c => c.position <= 2)
                                                .sort((a, b) => a.position - b.position)
                                                .map(c => {
                                                    const hit = isFinalized ? realIds.has(c.team_id) : null;
                                                    return (
                                                        <div key={c.team_id}
                                                             className={[
                                                                 'flex items-center gap-1.5 py-1 border-b border-dashed border-black/10',
                                                                 hit === false ? 'opacity-40' : '',
                                                             ].join(' ')}>
                                                            {isFinalized && (
                                                                <span className={`font-mono text-[10px] font-bold w-3.5 flex-shrink-0 ${hit ? 'text-pop-teal' : 'text-pop-red'}`}>
                                                                    {hit ? '✓' : '✗'}
                                                                </span>
                                                            )}
                                                            {c.flag_url && <img src={c.flag_url} alt="" className="h-3 w-4 object-cover flex-shrink-0" />}
                                                            <span className="font-display text-[10px] truncate leading-none">{(c.team_name ?? '?').toUpperCase()}</span>
                                                            <span className="font-mono text-[8px] opacity-40 ml-auto flex-shrink-0">{c.group}{c.position}°</span>
                                                        </div>
                                                    );
                                                })
                                        )
                                    }
                                </div>

                                {/* 8 mejores terceros */}
                                {bestThirds.length > 0 && (
                                    <div className="border-t-[2px] border-dashed border-ink/20 pt-2 pb-2">
                                        <div className="font-mono text-[8px] opacity-50 mb-1.5 tracking-[.06em]">8 MEJORES TERCEROS</div>
                                        <div className="flex flex-wrap gap-1">
                                            {bestThirds.map(c => {
                                                const hit = isFinalized ? realIds.has(c.team_id) : null;
                                                return (
                                                    <div key={c.team_id}
                                                         className={[
                                                             'flex items-center gap-1 border border-ink/20 px-1.5 py-0.5',
                                                             hit === true  ? 'bg-pop-teal/15' :
                                                             hit === false ? 'bg-pop-red/15 opacity-40' : 'bg-black/5',
                                                         ].join(' ')}>
                                                        {isFinalized && (
                                                            <span className={`font-mono text-[9px] font-bold ${hit ? 'text-pop-teal' : 'text-pop-red'}`}>
                                                                {hit ? '✓' : '✗'}
                                                            </span>
                                                        )}
                                                        {c.flag_url && <img src={c.flag_url} alt="" className="h-2.5 w-3.5 object-cover" />}
                                                        <span className="font-display text-[9px]">{(c.team_name ?? '?').toUpperCase()}</span>
                                                        <span className="font-mono text-[7px] opacity-40">({c.group})</span>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                })()}

                <div className="pb-10" />
            </div>
        </MobileShell>
    );
}
