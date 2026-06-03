import { useState, useEffect, useRef, forwardRef } from 'react';
import { Head } from '@inertiajs/react';
import MobileShell from '@/Components/MobileShell';
import TabBar from '@/Components/composed/TabBar';
import MatchCard from '@/Components/composed/MatchCard';
import GroupStandingCard from '@/Components/composed/GroupStandingCard';

function ViewTab({ label, active, last, onClick }) {
    return (
        <button
            onClick={onClick}
            className={[
                'flex-1 py-2 font-display text-[11px] text-center border-0',
                active ? 'bg-ink text-pop-yel' : 'bg-white text-ink',
                !last ? 'border-r-[2.5px] border-ink' : '',
            ].join(' ')}
        >
            {label}
        </button>
    );
}

const RoundChip = forwardRef(function RoundChip({ label, count, active, onClick }, ref) {
    return (
        <button
            ref={ref}
            onClick={onClick}
            className={[
                'flex-shrink-0 px-3 py-1.5 border-[2.5px] border-ink text-center',
                active ? 'bg-pop-red text-white shadow-pop' : 'bg-white text-ink shadow-pop-sm',
            ].join(' ')}
        >
            <div className="font-display text-[13px] leading-none">{label}</div>
            <div className="font-mono text-[9px] font-bold opacity-80 mt-0.5 tracking-[.06em]">{count}P</div>
        </button>
    );
});

const DayBlock = forwardRef(function DayBlock({ day }, ref) {
    return (
        <div ref={ref} className="mb-3">
            <div className="flex items-center gap-2 mb-1.5">
                <span
                    className={`w-3 h-3 border-2 border-ink flex-shrink-0 ${
                        day.live ? 'bg-pop-red' : 'bg-pop-teal'
                    }`}
                />
                <div className="font-display text-[14px]">{day.date}</div>
                <div className="flex-1 h-0.5 bg-ink" />
                <div className="font-mono text-[9px] opacity-65">{day.matches.length} partidos</div>
            </div>
            <div className="flex flex-col gap-2">
                {day.matches.map(m => (
                    <MatchCard key={m.id} {...m} />
                ))}
            </div>
        </div>
    );
});

export default function Matches({ matchDays: initialMatchDays, groups, fifaRounds, defaultFifaRound }) {
    const today = new Date().toISOString().split('T')[0];

    const [matchDays, setMatchDays]                = useState(initialMatchDays);
    const [view, setView]                          = useState('calendar');
    const [selectedFifaRound, setSelectedFifaRound] = useState(defaultFifaRound);

    const activeChipRef = useRef(null);
    const todayRef      = useRef(null);

    // Auto-scroll active chip into view on mount and on chip change
    useEffect(() => {
        activeChipRef.current?.scrollIntoView({ inline: 'center', behavior: 'instant', block: 'nearest' });
    }, [selectedFifaRound]);

    // Auto-scroll to today's DayBlock when round changes
    useEffect(() => {
        todayRef.current?.scrollIntoView({ behavior: 'instant', block: 'start' });
    }, [selectedFifaRound]);

    // Real-time score updates
    useEffect(() => {
        const channel = window.Echo.join('quinela');
        channel.listen('.LiveScoreUpdated', (event) => {
            setMatchDays(prev => prev.map(day => ({
                ...day,
                matches: day.matches.map(m =>
                    m.id === event.match_id
                        ? { ...m, home_score: event.home_score, away_score: event.away_score, status: event.status }
                        : m
                ),
            })));
        });
        return () => { window.Echo.leave('quinela'); };
    }, []);

    const visibleDays = matchDays
        .map(day => ({
            ...day,
            matches: day.matches.filter(m => m.fifaRound === selectedFifaRound),
        }))
        .filter(day => day.matches.length > 0);

    return (
        <>
            <Head title="Partidos" />
            <MobileShell>
                {/* Halftone decoration */}
                <div
                    className="halftone halftone-teal absolute top-[60px] right-0 w-[220px] h-[200px] pointer-events-none"
                    style={{ opacity: .2 }}
                />

                {/* Header */}
                <div className="relative px-[18px] pt-1.5 flex items-start justify-between">
                    <div>
                        <div className="font-mono text-[10px] opacity-70 tracking-[.1em] mt-1.5">WC 2026</div>
                        <div
                            className="font-display text-[32px] leading-none mt-0.5 text-pop-red"
                            style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}
                        >
                            PARTIDOS
                        </div>
                    </div>
                    <div className="mt-2">
                        <div className="font-mono text-[9px] opacity-65 tracking-[.06em]">
                            {matchDays.reduce((s, d) => s + d.matches.length, 0)} partidos
                        </div>
                    </div>
                </div>

                {/* View toggle */}
                <div className="px-[14px] pt-3">
                    <div className="flex border-[2.5px] border-ink shadow-pop">
                        <ViewTab
                            label="CALENDARIO"
                            active={view === 'calendar'}
                            onClick={() => setView('calendar')}
                        />
                        <ViewTab
                            label="POSICIONES"
                            active={view === 'standings'}
                            last
                            onClick={() => setView('standings')}
                        />
                    </div>
                </div>

                {view === 'calendar' ? (
                    <>
                        {/* Round chips */}
                        <div className="pt-3 pl-[14px]">
                            <div className="flex gap-1.5 overflow-x-auto pr-[14px] pb-1">
                                {fifaRounds.map(round => (
                                    <RoundChip
                                        key={round.slug}
                                        label={round.label}
                                        count={round.matchCount}
                                        active={selectedFifaRound === round.slug}
                                        ref={selectedFifaRound === round.slug ? activeChipRef : null}
                                        onClick={() => setSelectedFifaRound(round.slug)}
                                    />
                                ))}
                            </div>
                        </div>

                        {/* Match list */}
                        <div className="px-[14px] pt-2.5 pb-4">
                            {visibleDays.length > 0 ? (
                                visibleDays.map(day => (
                                    <DayBlock
                                        key={day.dateKey}
                                        day={day}
                                        ref={day.dateKey === today ? todayRef : null}
                                    />
                                ))
                            ) : (
                                <div className="text-center font-mono text-[11px] opacity-50 py-8">
                                    No hay partidos para esta ronda
                                </div>
                            )}
                            <div className="pt-2 text-center font-mono text-[10px] opacity-40 tracking-[.08em]">
                                · · · fin · · ·
                            </div>
                        </div>
                    </>
                ) : (
                    <>
                        {/* Group chips */}
                        <div className="pt-3 pl-[14px]">
                            <div className="flex gap-1.5 overflow-x-auto pr-[14px] pb-2">
                                {'ABCDEFGHIJKL'.split('').map(letter => (
                                    <div
                                        key={letter}
                                        className="flex-shrink-0 w-[42px] py-1.5 border-[2.5px] border-ink bg-white shadow-pop-sm text-center font-display text-[16px] leading-none"
                                    >
                                        {letter}
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="px-[14px] pb-4 flex flex-col gap-3">
                            {groups.map(group => (
                                <GroupStandingCard
                                    key={group.id}
                                    group={group.id}
                                    played={`${Math.floor(group.teams.reduce((s, t) => s + t.pj, 0) / 2)} / 6 JUGADOS`}
                                    teams={group.teams}
                                />
                            ))}
                        </div>
                    </>
                )}
            </MobileShell>
            <TabBar active="matches" />
        </>
    );
}
