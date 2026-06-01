import { Head, Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import MobileShell from '@/Components/MobileShell';
import TabBar from '@/Components/composed/TabBar';
import TournamentProgress from '@/Components/composed/TournamentProgress';
import PhaseCard from '@/Components/composed/PhaseCard';

export default function Index({ rounds, submissions, phasePts }) {
    const { auth, flash } = usePage().props;
    const totalPts = auth.user?.total_points ?? 0;

    return (
        <MobileShell>
            <Head title="Mis Fases · Mundial de Parche" />

            {/* Header */}
            <div className="px-[18px] pt-4 pb-0">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="font-mono text-[9px] tracking-[.1em] opacity-50">MUNDIAL 2026</div>
                        <div className="font-display text-[32px] leading-none mt-0.5">MIS FASES</div>
                    </div>
                    <div
                        className="bg-pop-yel text-ink border-[2.5px] border-ink px-2.5 py-1.5 text-right flex-shrink-0"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        <div className="font-display text-[22px] leading-none">{totalPts}</div>
                        <div className="font-mono text-[8px] tracking-[.06em] opacity-70">PTS TOTALES</div>
                    </div>
                </div>
            </div>

            {flash?.status && (
                <div className="mx-[18px] mt-3 px-3 py-2 bg-pop-yel border-[2px] border-ink font-mono text-[11px]">
                    {flash.status}
                </div>
            )}

            {/* Progress bar del torneo */}
            <TournamentProgress rounds={rounds} submissions={submissions} />

            {/* Divisor */}
            <div className="h-[3px] bg-ink mx-[18px]" />

            {/* Stack de phase cards */}
            <div className="px-[18px] py-4 flex flex-col gap-3">
                {/* Bloque especiales */}
                <SpecialsCard />

                {rounds.map(round => (
                    <PhaseCard
                        key={round.id}
                        round={round}
                        submission={submissions[round.id] ?? null}
                        phasePts={phasePts[round.id] ?? null}
                    />
                ))}
            </div>

            <div className="pb-6" />
            <TabBar active="home" />
        </MobileShell>
    );
}

function SpecialsCard() {
    return (
        <div
            className="border-[2.5px] border-dashed border-ink p-3.5 flex items-center justify-between"
            style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
        >
            <div>
                <div className="font-display text-[13px] leading-tight">PREDICCIONES ESPECIALES</div>
                <div className="font-mono text-[10px] opacity-60 mt-0.5">Campeón · Sub-campeón · Goleador</div>
            </div>
            <Link
                href={route('predictions.special')}
                className="ml-3 px-3 py-1.5 bg-ink text-cream font-display text-[11px] tracking-[.01em] border-[2px] border-ink flex-shrink-0"
                style={{ boxShadow: '2px 2px 0 var(--c-yel)' }}
            >
                VER →
            </Link>
        </div>
    );
}
