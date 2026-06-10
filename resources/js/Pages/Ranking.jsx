import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import MobileShell from '@/Components/MobileShell';
import TabBar from '@/Components/composed/TabBar';
import PodiumStep from '@/Components/composed/PodiumStep';
import RankRow from '@/Components/composed/RankRow';
import PozoCard from '@/Components/composed/PozoCard';
import { Trophy, SoccerBall, Mark26 } from '@/Components/icons/football';

const AVATAR_COLORS = {
    yel:   'var(--c-yel)',
    teal:  'var(--c-teal)',
    red:   'var(--c-red)',
    cream: 'var(--c-cream)',
};

function SectionHead({ title }) {
    return (
        <div className="flex items-center gap-2 py-2">
            <span className="w-3 h-3 flex-shrink-0 bg-pop-teal border-2 border-ink" />
            <div className="font-display text-[13px]">{title}</div>
            <div className="flex-1 h-0.5 bg-ink" />
        </div>
    );
}

function WinnersBanner({ podiumData, pozo }) {
    const gold   = podiumData[1];
    const silver = podiumData[2];

    const names = (tied) => tied.map(u => u.name).join(' & ');

    return (
        <div
            className="mx-[14px] border-[3px] border-ink bg-ink text-cream overflow-hidden relative"
            style={{ boxShadow: '5px 5px 0 var(--c-yel)' }}
        >
            {/* Halftone decoration */}
            <div
                className="absolute inset-0 pointer-events-none"
                style={{
                    backgroundImage: 'radial-gradient(var(--c-yel) 1.2px, transparent 1.6px)',
                    backgroundSize: '9px 9px',
                    opacity: 0.08,
                }}
            />

            {/* Mark26 watermark */}
            <div className="absolute right-[-8px] bottom-[-8px] opacity-10 pointer-events-none">
                <Mark26 size={90} fill="var(--c-yel)" />
            </div>

            {/* Header */}
            <div className="relative px-4 pt-3.5 pb-2 border-b border-white/10">
                <div className="font-mono text-[9px] tracking-[.14em] text-pop-yel opacity-90">
                    MUNDIAL FIFA 2026 · RESULTADO FINAL
                </div>
                <div
                    className="font-display text-[26px] leading-none mt-0.5 text-pop-yel"
                    style={{ textShadow: '3px 3px 0 var(--c-red)' }}
                >
                    CAMPEONES
                </div>
                <div className="font-display text-[14px] leading-none text-cream opacity-70">
                    DEL PARCHE
                </div>
            </div>

            {/* Prize rows */}
            <div className="relative divide-y divide-white/10">
                {/* 1st place */}
                {gold && (
                    <div className="flex items-center gap-3 px-4 py-3">
                        <div
                            className="w-9 h-9 flex-shrink-0 flex items-center justify-center border-[2.5px] border-pop-yel bg-pop-yel/10 font-display text-[18px] text-pop-yel"
                            style={{ boxShadow: '2px 2px 0 var(--c-yel)' }}
                        >
                            1°
                        </div>
                        <div className="flex-1 min-w-0">
                            <div className="font-display text-[16px] leading-tight text-pop-yel truncate">
                                {names(gold.tied)}
                            </div>
                            <div className="font-mono text-[10px] opacity-60 mt-0.5">
                                {gold.pts} PTS
                            </div>
                        </div>
                        <div className="flex-shrink-0 text-right">
                            <div className="font-display text-[22px] leading-none text-pop-yel">
                                {pozo.prize1}
                            </div>
                            <div className="font-mono text-[9px] opacity-50 tracking-[.06em]">PREMIO</div>
                        </div>
                    </div>
                )}

                {/* 2nd place */}
                {silver && (
                    <div className="flex items-center gap-3 px-4 py-3">
                        <div className="w-9 h-9 flex-shrink-0 flex items-center justify-center border-[2px] border-cream/40 font-display text-[16px] text-cream/70">
                            2°
                        </div>
                        <div className="flex-1 min-w-0">
                            <div className="font-display text-[15px] leading-tight text-cream truncate">
                                {names(silver.tied)}
                            </div>
                            <div className="font-mono text-[10px] opacity-50 mt-0.5">
                                {silver.pts} PTS
                            </div>
                        </div>
                        <div className="flex-shrink-0 text-right">
                            <div className="font-display text-[18px] leading-none text-cream">
                                {pozo.prize2}
                            </div>
                            <div className="font-mono text-[9px] opacity-40 tracking-[.06em]">PREMIO</div>
                        </div>
                    </div>
                )}
            </div>

            {/* Footer */}
            <div className="relative px-4 py-2 border-t border-white/10 bg-white/5">
                <div className="font-mono text-[9px] opacity-50 tracking-[.08em] text-center">
                    POZO TOTAL · {pozo.total} · {pozo.players} JUGADORES
                </div>
            </div>
        </div>
    );
}

function AllTiedHero({ users }) {
    const count = users.length;
    return (
        <div className="px-[14px] pt-[18px]">
            <div
                className="bg-pop-yel border-[3px] border-ink p-[14px_16px] relative overflow-hidden"
                style={{ boxShadow: '5px 5px 0 var(--c-ink)' }}
            >
                <div className="halftone halftone-red absolute top-0 right-0 w-40 h-40" style={{ opacity: .35 }} />
                <div className="flex items-center mb-2.5 relative">
                    {users.slice(0, 6).map((u, i) => (
                        <div
                            key={u.id}
                            className="w-9 h-9 rounded-full border-[2.5px] border-ink shadow-pop-sm font-display text-[14px] text-ink flex items-center justify-center flex-shrink-0"
                            style={{
                                background: AVATAR_COLORS[u.avatarColor] ?? 'var(--c-teal)',
                                marginLeft: i > 0 ? -12 : 0,
                                zIndex: 10 - i,
                            }}
                        >
                            {u.name[0]}
                        </div>
                    ))}
                    {count > 6 && (
                        <div
                            className="w-9 h-9 rounded-full bg-ink text-pop-yel border-[2.5px] border-ink font-display text-[11px] flex items-center justify-center flex-shrink-0"
                            style={{ marginLeft: -12, zIndex: 0 }}
                        >
                            +{count - 6}
                        </div>
                    )}
                </div>
                <div className="font-display text-[24px] leading-none text-ink">
                    {count} ARRANCAN<br />
                    <span className="text-pop-red" style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}>
                        EMPATADOS
                    </span>
                </div>
                <p className="font-body text-[11px] mt-1.5 leading-snug opacity-85 relative">
                    Nadie ha sumado puntos todavía. El podio se llena cuando arranquen los partidos.
                </p>
                <div className="absolute bottom-[-8px] right-[-6px] opacity-70" style={{ transform: 'rotate(-12deg)' }}>
                    <SoccerBall size={42} />
                </div>
            </div>
        </div>
    );
}

export default function Ranking({ users: initialUsers, pozo, tournamentFinalized }) {
    const { auth } = usePage().props;
    const me = auth.user.id;
    const [users, setUsers] = useState(initialUsers);

    // Real-time: update points via Echo
    useEffect(() => {
        const channel = window.Echo.join('quinela');
        channel.listen('.RankingUpdated', (event) => {
            setUsers(prev => {
                const byId = new Map(event.updates.map(u => [u.user_id, u]));
                const updated = prev.map(u =>
                    byId.has(u.id) ? { ...u, total_points: byId.get(u.id).total_points } : u
                );
                const sorted = [...updated].sort((a, b) => b.total_points - a.total_points);
                let pos = 0, lastPts = null, counter = 0;
                return sorted.map(u => {
                    counter++;
                    if (u.total_points !== lastPts) { pos = counter; lastPts = u.total_points; }
                    return { ...u, position: pos };
                });
            });
        });
        return () => { window.Echo.leave('quinela'); };
    }, []);

    const allTied = users.every(u => u.total_points === 0);

    // Count per points value for tiedCount display
    const ptsCounts = {};
    users.forEach(u => { ptsCounts[u.total_points] = (ptsCounts[u.total_points] || 0) + 1; });

    // Build podium: positions 1, 2, 3 by unique pts
    const uniquePts = [...new Set(users.map(u => u.total_points))].slice(0, 3);
    const podiumData = {};
    if (!allTied) {
        [1, 2, 3].forEach(place => {
            const pts = uniquePts[place - 1];
            if (pts !== undefined) {
                podiumData[place] = {
                    pts: String(pts),
                    tied: users
                        .filter(u => u.total_points === pts)
                        .map(u => ({
                            name:  u.name.split(' ')[0].toUpperCase(),
                            color: AVATAR_COLORS[u.avatarColor] ?? 'var(--c-teal)',
                        })),
                };
            }
        });
    }

    const listUsers = allTied ? users : users.filter(u => u.position > 3);

    return (
        <>
            <Head title="Ranking" />
            <MobileShell>
                {/* Halftone decoration */}
                <div
                    className="halftone halftone-red absolute top-[60px] right-0 w-[220px] h-[200px] pointer-events-none"
                    style={{ opacity: .25 }}
                />

                {/* Header */}
                <div className="relative px-[18px] pt-1.5 flex items-start justify-between">
                    <div>
                        <div
                            className="font-display text-[36px] leading-none mt-1.5 text-pop-yel"
                            style={{
                                WebkitTextStroke: '1.5px var(--c-ink)',
                                textShadow: '3px 3px 0 var(--c-red)',
                            }}
                        >
                            RANKING
                        </div>
                        <div className="font-mono text-[11px] opacity-70 tracking-[.08em] mt-0.5">
                            POR PUNTOS · {users.length} JUGADORES
                        </div>
                    </div>
                    <div className="mt-1.5" style={{ transform: 'rotate(8deg)' }}>
                        <Trophy size={40} />
                    </div>
                </div>

                {/* Pozo / Winners */}
                <div className="pt-2.5">
                    {tournamentFinalized && !allTied ? (
                        <WinnersBanner podiumData={podiumData} pozo={pozo} />
                    ) : (
                        <div className="px-[14px]">
                            <PozoCard
                                total={pozo.total}
                                players={pozo.players}
                                amountPerPlayer="50K"
                                prize1={pozo.prize1}
                                prize2={pozo.prize2}
                            />
                        </div>
                    )}
                </div>

                {/* Podium or AllTied */}
                {allTied ? (
                    <AllTiedHero users={users} />
                ) : (
                    <div className="px-[14px] pt-[18px] flex items-end justify-center gap-2">
                        {podiumData[2] && (
                            <PodiumStep place={2} pts={podiumData[2].pts} tied={podiumData[2].tied} />
                        )}
                        {podiumData[1] && (
                            <PodiumStep place={1} pts={podiumData[1].pts} tied={podiumData[1].tied} crown />
                        )}
                        {podiumData[3] && (
                            <PodiumStep place={3} pts={podiumData[3].pts} tied={podiumData[3].tied} />
                        )}
                    </div>
                )}

                {/* List */}
                <div className="px-[14px] pt-[14px] pb-4">
                    <SectionHead title={allTied ? 'TODOS EN CERO' : 'LOS DEMÁS'} />
                    <div className="flex flex-col gap-2 mt-1">
                        {listUsers.map(u => (
                            <RankRow
                                key={u.id}
                                position={u.position}
                                name={u.name.split(' ')[0].toUpperCase()}
                                pts={String(u.total_points)}
                                delta={u.delta ?? '+0'}
                                isYou={u.id === me}
                                tiedCount={ptsCounts[u.total_points] > 1 ? ptsCounts[u.total_points] : undefined}
                            />
                        ))}
                    </div>
                </div>
            </MobileShell>
            <TabBar active="rank" />
        </>
    );
}
