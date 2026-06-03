import { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import TabBar from '@/Components/composed/TabBar';
import PhaseOpenAlert from '@/Components/overlays/PhaseOpenAlert';
import DeadlineAlert from '@/Components/overlays/DeadlineAlert';
import StatCard from '@/Components/composed/StatCard';
import BetCard from '@/Components/composed/BetCard';
import PtsBadge from '@/Components/composed/PtsBadge';
import FeaturedMatchCard from '@/Components/composed/FeaturedMatchCard';

const AVATAR_CLASSES = {
    yel:   'bg-pop-yel text-ink',
    teal:  'bg-pop-teal text-ink',
    red:   'bg-pop-red text-white',
    cream: 'bg-ink text-cream border-2 border-cream',
};

function SectionHead({ title, accent = 'red' }) {
    return (
        <div className="flex items-center gap-2 py-2.5">
            <span className={`w-3.5 h-3.5 flex-shrink-0 bg-pop-${accent} border-2 border-ink`} />
            <div className="font-display text-[14px] tracking-[.02em]">{title}</div>
            <div className="flex-1 h-[3px] bg-ink" />
        </div>
    );
}

function Ticker({ text }) {
    const doubled = `${text}   ${text}`;
    return (
        <div className="overflow-hidden bg-ink py-1.5">
            <span className="inline-block whitespace-nowrap font-mono text-[11px] tracking-[.08em] text-pop-yel animate-[ticker_22s_linear_infinite]">
                {doubled}
            </span>
        </div>
    );
}

export default function Home({ user, featured, stats, phase, nextBets, phaseAlert, deadlineAlert }) {
    const { auth } = usePage().props;
    const avatarCls = AVATAR_CLASSES[user.avatarColor] ?? AVATAR_CLASSES.yel;
    const firstName = user.name.split(' ')[0].toUpperCase();
    const initial   = user.name.charAt(0).toUpperCase();

    const [featuredData, setFeaturedData] = useState(featured);
    const [totalPoints,  setTotalPoints]  = useState(user.totalPoints);
    const [position,     setPosition]     = useState(stats.position);

    // Sync featured from Inertia partial reload
    useEffect(() => { setFeaturedData(featured); }, [featured]);

    useEffect(() => {
        const channel = window.Echo.join('quinela');
        channel.listen('.LiveScoreUpdated', (e) => {
            setFeaturedData(prev => {
                if (prev?.id !== e.match_id) return prev;
                const newStatus = e.status === 'in_progress' ? 'live' : e.status;
                if (newStatus === 'finished') {
                    // Match done — reload featured to show the next upcoming match
                    router.reload({ only: ['featured'] });
                    return prev;
                }
                return { ...prev, scoreA: e.home_score, scoreB: e.away_score, status: newStatus };
            });
        });
        channel.listen('.PointsUpdated', (e) => {
            if (e.user_id === auth.user.id) {
                setTotalPoints(e.total_points);
                setPosition(e.position);
            }
        });
        return () => window.Echo.leave('quinela');
    }, []);

    const [alertDismissed, setAlertDismissed] = useState(() => {
        if (phaseAlert) {
            return localStorage.getItem(`alert_phase_${phaseAlert.toRound}`) === '1';
        }
        if (deadlineAlert) {
            return localStorage.getItem(`alert_deadline_${deadlineAlert.round}`) === '1';
        }
        return true;
    });

    const handleDismiss = () => {
        if (phaseAlert) {
            localStorage.setItem(`alert_phase_${phaseAlert.toRound}`, '1');
        } else if (deadlineAlert) {
            localStorage.setItem(`alert_deadline_${deadlineAlert.round}`, '1');
        }
        setAlertDismissed(true);
    };

    return (
        <>
            {!alertDismissed && phaseAlert && (
                <PhaseOpenAlert phaseAlert={phaseAlert} onDismiss={handleDismiss} />
            )}
            {!alertDismissed && !phaseAlert && deadlineAlert && (
                <DeadlineAlert deadlineAlert={deadlineAlert} onDismiss={handleDismiss} />
            )}
            <Head title="PARCHE" />
            <div className="min-h-screen bg-cream relative overflow-x-hidden pb-28">

                {/* Halftone decoration — top right */}
                <div
                    className="halftone halftone-yel absolute top-0 right-0 w-60 h-56 pointer-events-none"
                    style={{
                        WebkitMaskImage: 'radial-gradient(circle at 100% 0%, #000, transparent 70%)',
                        maskImage:       'radial-gradient(circle at 100% 0%, #000, transparent 70%)',
                        opacity: 0.18,
                    }}
                />

                {/* Header */}
                <div className="relative flex items-center justify-between px-[18px] pt-1.5">
                    <div className="flex items-center gap-2.5">
                        <div className={`w-9 h-9 rounded-full flex items-center justify-center font-display text-[16px] border-2 border-ink flex-shrink-0 ${avatarCls}`}>
                            {initial}
                        </div>
                        <div>
                            <div className="font-mono text-[11px] opacity-70 tracking-[.08em]">QUÉ MÁS, LLAVE</div>
                            <div className="font-display text-[18px] leading-none">{firstName}</div>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        
                        <a href={route('rules')}
                            className="w-9 h-9 border-[2.5px] border-ink bg-cream shadow-pop-sm font-display text-[18px] flex items-center justify-center cursor-pointer"
                            aria-label="Cómo se juega y reglas"
                        >
                            ?
                        </a>
                        <PtsBadge value={totalPoints} rank={`#${position}`} />
                    </div>
                </div>

                {/* Ticker */}
                <div className="mt-3.5">
                    <Ticker text="★ MUNDIAL 2026 EN VIVO · PREDICE TUS MARCADORES · ACUMULA PUNTOS · LLEVA EL RANKING · ¡PILAS! ★" />
                </div>

                {/* Activation banner */}
                {!user.isActivated ? (
                    <div className="px-[18px] mt-3.5">
                        <Link
                            href={route('activation')}
                            className="flex items-center justify-between px-3.5 py-2.5 bg-pop-yel border-[2.5px] border-ink"
                            style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                        >
                            <div>
                                <div className="font-display text-[13px] leading-none">POZO PENDIENTE</div>
                                <div className="font-mono text-[10px] opacity-70 mt-0.5">Todavía no estás en el parche</div>
                            </div>
                            <span className="font-display text-[13px]">ACTIVARME →</span>
                        </Link>
                    </div>
                ):(
                    <div className="px-[18px] mt-3.5">
                        <Link
                            href={route('predictions.index')}
                            className="flex items-center justify-center px-3.5 py-2.5 bg-pop-yel border-[2.5px] border-ink"
                            style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                        >
                            <div>
                                <div className="font-display text-[13px] leading-none">MIS GOLES →</div>
                            </div>
                        </Link>
                    </div>

                )}

                {/* Invite banner */}
                <div className="px-[18px] mt-3.5">
                    <a
                        href={`https://wa.me/?text=${encodeURIComponent('🏆 ¡Entra al Mundial de Parche! La polla para el Mundial FIFA 2026. Predice marcadores, clasificados y campeón. ' + route('home'))}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center justify-between px-3.5 py-2.5 bg-ink text-cream border-[2.5px] border-ink"
                        style={{ boxShadow: '3px 3px 0 var(--c-teal)' }}
                    >
                        <div>
                            <div className="font-display text-[13px] leading-none">INVITA AL PARCHE</div>
                            <div className="font-mono text-[10px] opacity-60 mt-0.5">Manda el link por WhatsApp</div>
                        </div>
                        <span className="font-display text-[20px]">📲</span>
                    </a>
                </div>

                {/* Featured match */}
                <div className="px-5 mt-5">
                    <SectionHead
                        title={featuredData?.status === 'live' ? 'AHORA MISMO' : 'EL PRÓXIMO'}
                        accent="red"
                    />
                    {featuredData ? (
                        <FeaturedMatchCard {...featuredData} />
                    ) : (
                        <div className="border-[2.5px] border-ink p-4 text-center font-mono text-[12px] opacity-60">
                            No hay partidos programados
                        </div>
                    )}
                </div>

                {/* Mini stats */}
                <div className="px-5 mt-5 grid grid-cols-3 gap-2">
                    <StatCard
                        label="POSICIÓN"
                        value={`#${position}`}
                        sub={`/ ${stats.totalActive}`}
                        color="red"
                        icon="trophy"
                    />
                    <StatCard
                        label="ACERTADOS"
                        value={stats.acertados}
                        sub="marcadores"
                        color="teal"
                        icon="ball"
                    />
                    <StatCard
                        label="RACHA"
                        value="--"
                        sub="ganadores"
                        color="yel"
                        icon="boot"
                    />
                </div>

                {/* Phase banner */}
                {user.isActivated && phase && phase.missing !== 0 && (
                    <div className="px-5 mt-4">
                        <div className="bg-pop-red text-white border-[2.5px] border-ink shadow-pop-md px-3 py-2.5 flex items-center gap-2.5 relative overflow-hidden">
                            <div className="halftone halftone-yel absolute inset-0 pointer-events-none" style={{ opacity: 0.25 }} />
                            <div className="relative flex-1">
                                <div className="font-mono text-[9px] tracking-[.1em] opacity-90">
                                    RONDA · {phase.name.toUpperCase()}
                                </div>
                                <div className="font-display text-[15px] mt-0.5 leading-tight">
                                    TE FALTAN{' '}
                                    <span className="text-pop-yel">{phase.missing}</span>{' '}
                                    PARTIDOS
                                </div>
                                {phase.closeDate && (
                                    <div className="font-mono text-[9px] opacity-85 mt-0.5">
                                        Cierre:{' '}
                                        {new Date(phase.closeDate).toLocaleDateString('es-CO', {
                                            day: 'numeric',
                                            month: 'short',
                                        })}
                                    </div>
                                )}
                            </div>
                            <div className="relative font-display text-[24px] leading-none">
                              <a href={route('predictions.index')}>→</a>  
                            </div>
                        </div>
                    </div>
                )}

                {/* Next bets carousel */}
                {nextBets.length > 0 && (
                    <div className="mt-4">
                        <div className="px-5">
                            <SectionHead title="TUS PRÓXIMOS" accent="teal" />
                        </div>
                        <div className="flex gap-2.5 overflow-x-auto px-5 pb-1">
                            {nextBets.map((bet, i) => (
                                <BetCard key={i} {...bet} />
                            ))}
                        </div>
                    </div>
                )}
            </div>

            <TabBar active="home" />
        </>
    );
}
