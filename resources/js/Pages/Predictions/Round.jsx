import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import MobileShell from '@/Components/MobileShell';
import TabBar from '@/Components/composed/TabBar';
import { Mark26 } from '@/Components/icons/football';

// ── helpers (preserved from original) ──────────────────────────────────────

function groupFixtures(fixtures) {
    return fixtures.reduce((acc, f) => {
        const key = f.group?.name ?? 'Sin Grupo';
        if (!acc[key]) acc[key] = [];
        acc[key].push(f);
        return acc;
    }, {});
}

function teamName(team, placeholder) {
    return team ? (team.fifa_code ?? team.name) : (placeholder ?? 'TBD');
}

// ── ScoreBoxInput — same visual as ScoreBox but editable ───────────────────

function ScoreBoxInput({ value, onChange, disabled }) {
    return (
        <div className="relative w-[30px] h-[34px] border-[2.5px] border-ink shadow-pop-sm flex items-center justify-center font-display text-[18px]"
            style={{ background: value !== null && value !== undefined ? 'var(--c-yel)' : '#fff',
                     color: value !== null && value !== undefined ? 'var(--c-ink)' : 'rgba(0,0,0,.25)' }}
        >
            {value !== null && value !== undefined ? value : '—'}
            <input
                type="number"
                min="0"
                max="20"
                value={value ?? ''}
                onChange={e => onChange(parseInt(e.target.value, 10))}
                disabled={disabled}
                className="absolute inset-0 opacity-0 cursor-pointer disabled:cursor-default w-full h-full"
                aria-label="goles"
            />
        </div>
    );
}

// ── PointChip ──────────────────────────────────────────────────────────────

function PointChip({ label, pts, color }) {
    return (
        <div className="flex-1 bg-white border-[2.5px] border-ink shadow-pop-sm p-[4px_6px] flex items-center justify-between gap-1">
            <div className="font-mono text-[9px] font-bold tracking-[.06em]">{label}</div>
            <div
                className="font-display text-[12px] px-[6px] py-[1px] border-[1.5px] border-ink text-white"
                style={{ background: color }}
            >
                {pts}
            </div>
        </div>
    );
}

// ── GroupChip ──────────────────────────────────────────────────────────────

function GroupChip({ groupKey, active, done, teams, onClick }) {
    return (
        <button
            onClick={onClick}
            className={[
                'flex-shrink-0 min-w-[78px] px-2.5 py-1.5 border-[2.5px] border-ink text-center relative',
                active
                    ? 'bg-ink text-pop-yel shadow-[3px_3px_0_var(--c-red)]'
                    : done
                        ? 'bg-pop-teal text-white shadow-pop-sm'
                        : 'bg-white text-ink shadow-pop-sm',
            ].join(' ')}
        >
            <div className="font-display text-[20px] leading-none">{groupKey}</div>
            <div className="flex justify-center gap-0.5 mt-1">
                {(teams ?? []).slice(0, 4).map((t, i) => (
                    t.flagUrl
                        ? <img key={i} src={t.flagUrl} alt="" className="h-2.5 w-4 object-cover" />
                        : <span key={i} className="w-4 h-2.5 bg-black/20 inline-block" />
                ))}
            </div>
            {done && (
                <div className="absolute -top-1.5 -right-1.5 w-[18px] h-[18px] rounded-full bg-pop-yel border-2 border-ink text-ink font-display text-[10px] flex items-center justify-center">
                    ✓
                </div>
            )}
        </button>
    );
}

// ── WinnerPicker — selector de quien avanza en empate de knockout ─────────

function WinnerPicker({ homeTeam, awayTeam, selectedId, onSelect, disabled }) {
    const teams = [homeTeam, awayTeam].filter(Boolean);
    if (teams.length < 2) return null;

    return (
        <div className="mt-2 px-2 pb-1">
            <div className="font-mono text-[8px] tracking-[.1em] opacity-60 text-center mb-1.5">
                ¿QUIÉN AVANZA? (ET / PENALES)
            </div>
            <div className="flex gap-1.5">
                {teams.map(t => {
                    const active = selectedId === t.id;
                    return (
                        <button
                            key={t.id}
                            type="button"
                            disabled={disabled}
                            onClick={() => onSelect(active ? null : t.id)}
                            className={[
                                'flex-1 flex items-center justify-center gap-1.5 py-1.5 border-[2px] border-ink font-display text-[11px] transition-colors',
                                active
                                    ? 'bg-pop-teal text-white'
                                    : 'bg-white text-ink opacity-70',
                            ].join(' ')}
                            style={active ? { boxShadow: '2px 2px 0 var(--c-ink)' } : {}}
                        >
                            {t.flag_url && <img src={t.flag_url} alt="" className="h-3.5 w-5 object-cover" />}
                            {t.fifa_code ?? t.name}
                            {active && <span className="text-[9px]"> ✓</span>}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

// ── MatchPredRow ──────────────────────────────────────────────────────────

function MatchPredRow({ fixture, homeScore, awayScore, onChangeHome, onChangeAway, disabled, last,
                        simulatedHome, simulatedAway, isKnockout, predictedWinnerId, onSelectWinner }) {
    const filled = homeScore !== null && homeScore !== undefined
                && awayScore !== null && awayScore !== undefined;

    const resolvedHome = fixture.home_team ?? simulatedHome;
    const resolvedAway = fixture.away_team ?? simulatedAway;

    const home     = resolvedHome ? (resolvedHome.fifa_code ?? resolvedHome.name) : (fixture.home_placeholder ?? 'TBD');
    const away     = resolvedAway ? (resolvedAway.fifa_code ?? resolvedAway.name) : (fixture.away_placeholder ?? 'TBD');
    const flagHome = resolvedHome?.flag_url ?? null;
    const flagAway = resolvedAway?.flag_url ?? null;
    const homeSimulated = !fixture.home_team && !!simulatedHome;
    const awaySimulated = !fixture.away_team && !!simulatedAway;

    return (
        <div className={['px-2.5 py-2 relative', !last ? 'border-b border-dashed border-black/20' : ''].join(' ')}>
            <div className="font-mono text-[8.5px] opacity-55 tracking-[.08em] mb-1">
                {fixture.match_date
                    ? new Date(fixture.match_date).toLocaleString('es', {
                        day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
                      })
                    : '--'
                }
                {fixture.venue ? ` · ${fixture.venue}` : ''}
            </div>
            <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                <div className="flex items-center justify-end gap-1.5">
                    <span className={['font-display text-8', homeSimulated ? 'opacity-50 italic' : ''].join(' ')}>
                        {home}
                    </span>
                    {flagHome && <img src={flagHome} alt={home} className="h-8 w-12 object-cover border border-ink" />}
                </div>
                <div className="flex items-center gap-0.5">
                    <ScoreBoxInput value={homeScore} onChange={onChangeHome} disabled={disabled} />
                    <span className="font-display text-[13px] opacity-55 mx-0.5">—</span>
                    <ScoreBoxInput value={awayScore} onChange={onChangeAway} disabled={disabled} />
                </div>
                <div className="flex items-center gap-1.5">
                    {flagAway && <img src={flagAway} alt={away} className="h-8 w-12 object-cover border border-ink" />}
                    <span className={['font-display text-8', awaySimulated ? 'opacity-50 italic' : ''].join(' ')}>
                        {away}
                    </span>
                </div>
            </div>
            {(homeSimulated || awaySimulated) && (
                <div className="text-center font-mono text-[7.5px] opacity-40 mt-0.5 tracking-[.06em]">
                    rival simulado de tus predicciones
                </div>
            )}

            {/* Winner picker: solo en knockout cuando el marcador es empate */}
            {isKnockout && filled && Number(homeScore) === Number(awayScore) && (
                <WinnerPicker
                    homeTeam={resolvedHome}
                    awayTeam={resolvedAway}
                    selectedId={predictedWinnerId}
                    onSelect={onSelectWinner}
                    disabled={disabled}
                />
            )}

            <div className="flex justify-center mt-2">
                {filled && (!isKnockout || Number(homeScore) !== Number(awayScore) || predictedWinnerId) ? (
                    <span className="inline-flex items-center gap-1 font-mono text-[8.5px] font-bold tracking-[.08em] bg-pop-teal text-white px-1.5 py-0.5 border-[1.5px] border-ink">
                        ✓ GUARDADO
                    </span>
                ) : filled && isKnockout && Number(homeScore) === Number(awayScore) ? (
                    <span className="inline-flex items-center gap-1 font-mono text-[8.5px] font-bold tracking-[.08em] bg-pop-yel text-ink px-1.5 py-0.5 border-[1.5px] border-ink">
                        ↑ ELIGE QUIÉN AVANZA
                    </span>
                ) : (
                    <span className="inline-flex items-center gap-1 font-mono text-[8.5px] font-bold tracking-[.08em] bg-white text-pop-red px-1.5 py-0.5 border-[1.5px] border-dashed border-pop-red">
                        ! FALTAN TUS GOLES
                    </span>
                )}
            </div>
        </div>
    );
}

// ── simulateStandings — tabla de posiciones basada en predicciones del usuario

function simulateStandings(fixtures, scores) {
    const table = {};

    // Init all teams first so aparecen aunque no tengan partidos predichos
    fixtures.forEach(f => {
        if (f.home_team && !table[f.home_team.id]) {
            table[f.home_team.id] = { team: f.home_team, pts: 0, gf: 0, ga: 0, played: 0 };
        }
        if (f.away_team && !table[f.away_team.id]) {
            table[f.away_team.id] = { team: f.away_team, pts: 0, gf: 0, ga: 0, played: 0 };
        }
    });

    // Acumular stats de los partidos ya predichos
    fixtures.forEach(f => {
        const s = scores[f.id];
        if (!f.home_team || !f.away_team) return;
        if (s == null || s.home == null || s.away == null) return;

        const h = Number(s.home);
        const a = Number(s.away);

        table[f.home_team.id].gf += h;
        table[f.home_team.id].ga += a;
        table[f.home_team.id].played++;

        table[f.away_team.id].gf += a;
        table[f.away_team.id].ga += h;
        table[f.away_team.id].played++;

        if (h > a)      { table[f.home_team.id].pts += 3; }
        else if (h < a) { table[f.away_team.id].pts += 3; }
        else            { table[f.home_team.id].pts += 1; table[f.away_team.id].pts += 1; }
    });

    return Object.values(table).sort((a, b) => {
        if (b.pts !== a.pts)         return b.pts - a.pts;
        const gdDiff = (b.gf - b.ga) - (a.gf - a.ga);
        if (gdDiff !== 0)            return gdDiff;
        return b.gf - a.gf;
    });
}

// ── simulateAllGroups — simula los 32 clasificados de toda la fase de grupos

function simulateAllGroups(fixtures, scores) {
    // Agrupar por nombre de grupo
    const byGroup = {};
    fixtures.forEach(f => {
        const key = f.group?.name ?? 'Sin Grupo';
        if (!byGroup[key]) byGroup[key] = [];
        byGroup[key].push(f);
    });

    const classifiers = [];
    const thirdsPool  = [];

    Object.entries(byGroup).forEach(([groupName, groupFixtures]) => {
        const standings = simulateStandings(groupFixtures, scores);
        standings.forEach((row, i) => {
            const entry = { team_id: row.team.id, group: groupName, position: i + 1 };
            if (i < 2) {
                classifiers.push(entry);
            } else if (i === 2) {
                thirdsPool.push({ ...entry, pts: row.pts, gf: row.gf, ga: row.ga });
            }
        });
    });

    // Seleccionar los 8 mejores terceros (pts → GD → GF)
    thirdsPool.sort((a, b) => {
        if (b.pts !== a.pts) return b.pts - a.pts;
        const gdDiff = (b.gf - b.ga) - (a.gf - a.ga);
        if (gdDiff !== 0) return gdDiff;
        return b.gf - a.gf;
    });

    thirdsPool.slice(0, 8).forEach(({ team_id, group, position }) => {
        classifiers.push({ team_id, group, position });
    });

    return classifiers; // 32 entradas: {team_id, group, position}
}

// ── Bracket simulation ────────────────────────────────────────────────────────
function simulateBracketWinner(matchNumber, fixturesByMatchNumber, scores, winners) {
    const fixture = fixturesByMatchNumber[matchNumber];
    if (!fixture) return null;

    const homeTeam = fixture.home_team
        ?? (fixture.home_fed_by_match_number
            ? simulateBracketWinner(fixture.home_fed_by_match_number, fixturesByMatchNumber, scores, winners)
            : null);

    const awayTeam = fixture.away_team
        ?? (fixture.away_fed_by_match_number
            ? simulateBracketWinner(fixture.away_fed_by_match_number, fixturesByMatchNumber, scores, winners)
            : null);

    if (!homeTeam || !awayTeam) return null;

    const s = scores[fixture.id];
    if (s?.home == null || s?.away == null) return null;

    const h = Number(s.home);
    const a = Number(s.away);
    if (h !== a) return h > a ? homeTeam : awayTeam;

    // Empate: usar predicted winner (ET/penales)
    const winnerId = winners[fixture.id];
    if (!winnerId) return null;
    return homeTeam.id === winnerId ? homeTeam : awayTeam;
}

function getBracketTeam(fixture, slot, fixturesByMatchNumber, scores, winners) {
    const realTeam = slot === 'home' ? fixture.home_team : fixture.away_team;
    if (realTeam) return { team: realTeam, isSimulated: false };

    const fedBy = slot === 'home'
        ? fixture.home_fed_by_match_number
        : fixture.away_fed_by_match_number;
    if (!fedBy) return { team: null, isSimulated: false };

    return { team: simulateBracketWinner(fedBy, fixturesByMatchNumber, scores, winners), isSimulated: true };
}

// ── GroupPanel ─────────────────────────────────────────────────────────────

function GroupPanel({ groupKey, fixtures, scores, isLocked, onChange, round }) {
    const standings = simulateStandings(fixtures, scores);
    const anyPredicted = fixtures.some(f => {
        const s = scores[f.id];
        return s && s.home !== null && s.away !== null;
    });
    const filled = fixtures.filter(f => {
        const s = scores[f.id];
        return s && s.home !== null && s.home !== undefined
                 && s.away !== null && s.away !== undefined;
    }).length;

    return (
        <div className="border-[3px] border-ink bg-white relative overflow-hidden" style={{ boxShadow: '5px 5px 0 var(--c-ink)' }}>
            {/* corner banner */}
            <div className="absolute top-0 left-0 bg-pop-red text-white px-3 py-1.5 font-display text-[14px] border-r-[3px] border-b-[3px] border-ink">
                GRUPO {groupKey}
            </div>
            <div className="absolute top-1.5 right-2.5 font-mono text-[10px] opacity-70">
                {filled} / {fixtures.length} GOLES METIDOS
            </div>

            {/* TUS CLASIFICADOS */}
            {standings.length > 0 && (
                <div className="mt-10 px-2.5 pb-2 border-b-2 border-dashed border-ink">
                    <div className="flex justify-between items-baseline mb-1.5">
                        <div className="font-mono text-[9px] font-bold tracking-[.08em] opacity-70">
                            TUS CLASIFICADOS
                            {!anyPredicted && <span className="opacity-50 ml-1">(meta tus goles)</span>}
                        </div>
                        <div className="font-mono text-[8.5px] opacity-55">+{round.points_classifier} PTS C/U</div>
                    </div>
                    <div className="grid grid-cols-2 gap-1.5">
                        {standings.map((row, i) => {
                            const advances = i < 2;
                            const t = row.team;
                            return (
                                <div
                                    key={t.id}
                                    className={[
                                        'flex items-center gap-1.5 px-1.5 py-1 border-[1.5px] border-ink',
                                        advances && anyPredicted ? 'bg-pop-yel' : 'bg-black/4 opacity-60',
                                    ].join(' ')}
                                >
                                    <span className="font-mono text-[9px] font-bold opacity-60 w-3.5">{i + 1}°</span>
                                    {t.flag_url && <img src={t.flag_url} alt="" className="h-3 w-4 object-cover" />}
                                    <span className="font-display text-[10px] flex-1 leading-none truncate">{t.name.toUpperCase()}</span>
                                    {advances && anyPredicted && (
                                        <span className="font-mono text-[8px] font-bold text-pop-teal">→R32</span>
                                    )}
                                    {anyPredicted && (
                                        <span className="font-mono text-[8px] opacity-50 ml-auto">{row.pts}p</span>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Matches */}
            <div>
                {fixtures.map((f, i) => (
                    <MatchPredRow
                        key={f.id}
                        fixture={f}
                        homeScore={scores[f.id]?.home ?? null}
                        awayScore={scores[f.id]?.away ?? null}
                        onChangeHome={v => onChange(f.id, 'home', v)}
                        onChangeAway={v => onChange(f.id, 'away', v)}
                        disabled={isLocked}
                        last={i === fixtures.length - 1}
                    />
                ))}
            </div>
        </div>
    );
}

// ── Main Page ──────────────────────────────────────────────────────────────

export default function Round({ round, fixtures, predictions, submission }) {
    const { auth, flash } = usePage().props;
    const isActivated = auth.user.is_activated;
    const isLocked    = submission?.status === 'locked';
    const isSubmitted = submission?.status === 'submitted';
    const isGroupStage = round.slug === 'grupos';
    const [drawError, setDrawError] = useState(null);

    // Initialize scores from existing predictions
    const initialScores = {};
    const initialWinners = {};
    fixtures.forEach(f => {
        const pred = predictions[f.id];
        initialScores[f.id] = {
            home: pred ? pred.predicted_home : null,
            away: pred ? pred.predicted_away : null,
        };
        initialWinners[f.id] = pred?.predicted_winner_id ?? null;
    });

    const [scores,  setScores]  = useState(initialScores);
    const [winners, setWinners] = useState(initialWinners);

    // Build lookup map for bracket simulation
    const fixturesByMatchNumber = Object.fromEntries(fixtures.map(f => [f.match_number, f]));

    const isBracketPhase = fixtures.some(
        f => f.home_fed_by_match_number !== null || f.away_fed_by_match_number !== null
    );

    const grouped    = groupFixtures(fixtures);
    const groupKeys  = Object.keys(grouped).sort();
    const [activeGroup, setActiveGroup] = useState(groupKeys[0] ?? null);

    function handleChange(fixtureId, side, value) {
        if (isLocked || isSubmitted) return;
        setDrawError(null);
        setScores(prev => ({
            ...prev,
            [fixtureId]: { ...prev[fixtureId], [side]: isNaN(value) ? null : value },
        }));
        // Al cambiar marcador ya no es empate, limpiar winner
        setWinners(prev => ({ ...prev, [fixtureId]: null }));
    }

    function handleSelectWinner(fixtureId, teamId) {
        if (isLocked || isSubmitted) return;
        setWinners(prev => ({ ...prev, [fixtureId]: teamId }));
    }

    function isGroupDone(key) {
        return (grouped[key] ?? []).every(f => {
            const s = scores[f.id];
            return s && s.home !== null && s.away !== null;
        });
    }

    const totalFixtures = fixtures.length;
    const filledCount   = fixtures.filter(f => {
        const s = scores[f.id];
        return s && s.home !== null && s.away !== null;
    }).length;
    const progressPct = totalFixtures > 0 ? filledCount / totalFixtures : 0;

    function buildPayload() {
        const preds = {};
        fixtures.forEach(f => {
            const s = scores[f.id];
            if (s && s.home !== null && s.away !== null) {
                const entry = {
                    predicted_home: Number(s.home),
                    predicted_away: Number(s.away),
                };
                if (!isGroupStage && Number(s.home) === Number(s.away) && winners[f.id]) {
                    entry.predicted_winner_id = winners[f.id];
                }
                preds[String(f.id)] = entry;
            }
        });
        return { predictions: preds };
    }

    function submit() {
        const payload = buildPayload();

        if (!isGroupStage) {
            const drawsWithoutWinner = fixtures.filter(f => {
                const s = scores[f.id];
                if (!s || s.home === null || s.away === null) return false;
                return Number(s.home) === Number(s.away) && !winners[f.id];
            });
            if (drawsWithoutWinner.length > 0) {
                setDrawError('Si predices empate, elige quién avanza por ET/penales en cada partido empatado.');
                return;
            }
        }

        setDrawError(null);

        if (isGroupStage && filledCount === totalFixtures) {
            payload.predicted_classifiers = simulateAllGroups(fixtures, scores);
        }

        router.post(route('predictions.save', round.slug), payload);
    }

    const activeFixtures = activeGroup ? (grouped[activeGroup] ?? []) : [];

    return (
        <>
            <Head title="Mis Goles" />
            <div className="bg-cream min-h-screen overflow-x-hidden flex flex-col relative" style={{ paddingBottom: '128px' }}>

                {/* Halftone decoration */}
                <div
                    className="halftone halftone-yel absolute top-[60px] right-[-20px] w-[220px] h-[200px] pointer-events-none"
                    style={{ opacity: .25 }}
                />

                {/* Header */}
                <div className="relative px-[18px] pt-1.5 flex items-start justify-between">
                    <div>
                        <div className="font-mono text-[10px] opacity-70 tracking-[.1em] mt-1.5">MUNDIAL 2026</div>
                        <div className="font-display text-[32px] leading-none mt-0.5">
                            MIS{' '}
                            <span className="text-pop-red" style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}>
                                GOLES
                            </span>
                        </div>
                    </div>
                    <div className="flex flex-col items-end gap-1.5 mt-1.5">
                        {auth.user.is_activated && (
                        <div
                            className="inline-flex items-center gap-1.5 bg-pop-teal text-white border-2 border-ink px-2 py-1 font-mono text-[10px] font-bold tracking-[.06em]"
                            style={{ boxShadow: '2px 2px 0 var(--c-ink)' }}
                        >
                            ✓ ENTRADA 50K PAGA
                        </div>
                        )}
                        <div style={{ transform: 'rotate(6deg)' }}>
                            <Mark26 size={26} fill="var(--c-red)" accent="var(--c-yel)" />
                        </div>
                    </div>
                </div>

                {/* Phase card navy */}
                <div className="px-[18px] pt-3">
                    <div className="border-[3px] border-ink bg-navy text-cream p-[10px_12px] relative overflow-hidden" style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}>
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="font-mono text-[10px] text-pop-yel tracking-[.12em]">FASE EN CURSO</div>
                                <div className="font-display text-[18px] leading-none mt-0.5">{round.name.toUpperCase()}</div>
                            </div>
                            <div className="text-right font-mono text-[11px]">
                                <div className="text-pop-yel font-bold">{filledCount} / {totalFixtures}</div>
                                <div className="opacity-70">goles metidos</div>
                            </div>
                        </div>
                        <div className="mt-2 h-[5px] bg-black/25 border border-ink">
                            <div className="h-full bg-pop-yel transition-all" style={{ width: `${progressPct * 100}%` }} />
                        </div>
                    </div>
                </div>

                {/* Point chips */}
                <div className="px-[14px] pt-2.5 flex gap-1.5">
                    <PointChip label="EXACTO"    pts={`+${round.points_exact}`}  color="var(--c-red)"  />
                    <PointChip label="GANADOR"   pts={`+${round.points_result}`} color="var(--c-teal)" />
                    {round.points_classifier > 0 && (
                        <PointChip label="CLASIFICA" pts={`+${round.points_classifier}`} color="var(--c-yel)" />
                    )}
                </div>

                {/* Group chips — only for group stage */}
                {isGroupStage && groupKeys.length > 0 && (
                    <div className="pt-3 pl-[14px]">
                        <div className="flex items-center justify-between px-1 pb-2">
                            <div className="font-display text-[14px]">ELEGÍ GRUPO</div>
                            <div className="font-mono text-[10px] opacity-65">{groupKeys.length} grupos</div>
                        </div>
                        <div className="flex gap-1.5 overflow-x-auto pr-[14px] pb-1.5">
                            {groupKeys.map(key => (
                                <GroupChip
                                    key={key}
                                    groupKey={key}
                                    active={key === activeGroup}
                                    done={isGroupDone(key) && key !== activeGroup}
                                    teams={(grouped[key] ?? []).flatMap(f => [
                                        f.home_team ? { flagUrl: f.home_team.flag_url } : null,
                                        f.away_team ? { flagUrl: f.away_team.flag_url } : null,
                                    ]).filter(Boolean).slice(0, 4)}
                                    onClick={() => setActiveGroup(key)}
                                />
                            ))}
                        </div>
                    </div>
                )}

                {/* GroupPanel — only for group stage */}
                {isGroupStage && (
                    <div className="px-[14px] pt-3">
                        {activeGroup && (
                            <GroupPanel
                                groupKey={activeGroup}
                                fixtures={activeFixtures}
                                scores={scores}
                                isLocked={isLocked || isSubmitted || !isActivated}
                                onChange={handleChange}
                                round={round}
                            />
                        )}
                    </div>
                )}

                {/* Bracket Phase — knockout fixtures with simulated opponents */}
                {!isGroupStage && (
                    <div className="px-[14px] pt-3">
                        <div className="border-[3px] border-ink bg-white relative overflow-hidden" style={{ boxShadow: '5px 5px 0 var(--c-ink)' }}>
                            <div className="absolute top-0 left-0 bg-pop-red text-white px-3 py-1.5 font-display text-[14px] border-r-[3px] border-b-[3px] border-ink">
                                {round.name.toUpperCase()}
                            </div>
                            <div className="absolute top-1.5 right-2.5 font-mono text-[10px] opacity-70">
                                {filledCount} / {totalFixtures} GOLES METIDOS
                            </div>
                            <div className="mt-10">
                                {fixtures.map((f, i) => {
                                    const simHome = isBracketPhase ? getBracketTeam(f, 'home', fixturesByMatchNumber, scores, winners) : { team: null, isSimulated: false };
                                    const simAway = isBracketPhase ? getBracketTeam(f, 'away', fixturesByMatchNumber, scores, winners) : { team: null, isSimulated: false };
                                    return (
                                        <MatchPredRow
                                            key={f.id}
                                            fixture={f}
                                            homeScore={scores[f.id]?.home ?? null}
                                            awayScore={scores[f.id]?.away ?? null}
                                            onChangeHome={v => handleChange(f.id, 'home', v)}
                                            onChangeAway={v => handleChange(f.id, 'away', v)}
                                            disabled={isLocked || isSubmitted || !isActivated}
                                            last={i === fixtures.length - 1}
                                            simulatedHome={simHome.isSimulated ? simHome.team : null}
                                            simulatedAway={simAway.isSimulated ? simAway.team : null}
                                            isKnockout={true}
                                            predictedWinnerId={winners[f.id] ?? null}
                                            onSelectWinner={teamId => handleSelectWinner(f.id, teamId)}
                                        />
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                )}

                {/* Panel TUS 32 CLASIFICADOS — visible solo cuando todos los partidos están predichos */}
                {isGroupStage && filledCount === totalFixtures && (() => {
                    const allClassifiers = simulateAllGroups(fixtures, scores);
                    const teamById = {};
                    fixtures.forEach(f => {
                        if (f.home_team) teamById[f.home_team.id] = f.home_team;
                        if (f.away_team) teamById[f.away_team.id] = f.away_team;
                    });
                    const byGroup = {};
                    allClassifiers.forEach(c => {
                        if (!byGroup[c.group]) byGroup[c.group] = [];
                        byGroup[c.group].push(c);
                    });
                    const bestThirds = allClassifiers.filter(c => c.position === 3);

                    return (
                        <div className="px-[14px] pt-4">
                            <div className="border-[3px] border-ink bg-navy text-cream p-3.5 relative overflow-hidden"
                                 style={{ boxShadow: '5px 5px 0 var(--c-yel)' }}>
                                <div className="font-mono text-[9px] tracking-[.1em] text-pop-yel opacity-90 mb-1">
                                    SEGÚN TUS PREDICCIONES
                                </div>
                                <div className="font-display text-[18px] leading-none mb-3">
                                    TUS 32 CLASIFICADOS
                                </div>

                                {/* Grid por grupo */}
                                <div className="grid grid-cols-2 gap-x-2 gap-y-1 mb-3">
                                    {Object.entries(byGroup)
                                        .sort(([a], [b]) => a.localeCompare(b))
                                        .map(([groupName, entries]) => {
                                            const first  = entries.find(e => e.position === 1);
                                            const second = entries.find(e => e.position === 2);
                                            const team1 = first  ? teamById[first.team_id]  : null;
                                            const team2 = second ? teamById[second.team_id] : null;
                                            return (
                                                <div key={groupName} className="bg-white/10 px-2 py-1.5 border border-cream/20">
                                                    <div className="font-mono text-[8px] opacity-60 mb-1">GRUPO {groupName}</div>
                                                    {[team1, team2].map((t, i) => t && (
                                                        <div key={i} className="flex items-center gap-1 mb-0.5">
                                                            <span className="font-mono text-[8px] opacity-50 w-3">{i + 1}°</span>
                                                            {t.flag_url && <img src={t.flag_url} alt="" className="h-2.5 w-3.5 object-cover" />}
                                                            <span className="font-display text-[9px] leading-none truncate">{(t.fifa_code ?? t.name).toUpperCase()}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            );
                                        })
                                    }
                                </div>

                                {/* 8 mejores terceros */}
                                {bestThirds.length > 0 && (
                                    <div className="border-t border-cream/20 pt-2.5">
                                        <div className="font-mono text-[8px] opacity-60 mb-1.5">8 MEJORES TERCEROS</div>
                                        <div className="flex flex-wrap gap-1">
                                            {bestThirds.map(c => {
                                                const t = teamById[c.team_id];
                                                return t ? (
                                                    <div key={c.team_id} className="flex items-center gap-1 bg-white/10 px-1.5 py-0.5 border border-cream/20">
                                                        {t.flag_url && <img src={t.flag_url} alt="" className="h-2.5 w-3.5 object-cover" />}
                                                        <span className="font-display text-[9px]">{(t.fifa_code ?? t.name).toUpperCase()}</span>
                                                        <span className="font-mono text-[7px] opacity-50">({c.group})</span>
                                                    </div>
                                                ) : null;
                                            })}
                                        </div>
                                    </div>
                                )}

                                <div className="mt-3 font-mono text-[9px] opacity-60 leading-[1.4]">
                                    Estos se guardarán cuando confirmes tus predicciones →
                                </div>
                            </div>
                        </div>
                    );
                })()}
            </div>

            {/* Flash / error banners */}
            {(drawError || flash?.status) && (
                <div className="fixed bottom-[140px] left-[14px] right-[14px] z-50 flex flex-col gap-2">
                    {drawError && (
                        <div className="bg-pop-red text-white border-[2.5px] border-ink px-3 py-2 font-mono text-[10px] leading-[1.4]"
                             style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}>
                            ⚠ {drawError}
                        </div>
                    )}
                    {flash?.status && (
                        <div className="bg-pop-teal text-white border-[2.5px] border-ink px-3 py-2 font-mono text-[10px] font-bold tracking-[.06em]"
                             style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}>
                            ✓ {flash.status}
                        </div>
                    )}
                </div>
            )}

            {/* Sticky CTA */}
            <div className="fixed bottom-[72px] left-0 right-0 bg-cream border-t-[3px] border-ink px-[14px] py-2.5 flex items-center gap-3 z-40">
                <div className="flex-1">
                    <div className="font-mono text-[10px] opacity-70 tracking-[.08em]">TU PUNTAJE ACTUAL</div>
                    <div className="font-display text-[18px] leading-none mt-0.5">
                        {auth.user.total_points ?? 0} PTS
                    </div>
                </div>
                {isActivated ? (
                    <button
                        onClick={submit}
                        disabled={isLocked || isSubmitted}
                        className="py-3 px-4 bg-pop-red text-white font-display text-[13px] border-[2.5px] border-ink disabled:opacity-50"
                        style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                    >
                        {isLocked ? 'BLOQUEADO 🔒' : isSubmitted ? 'ENVIADO ✓' : 'GUARDAR MIS GOLES →'}
                    </button>
                ) : (
                    <Link
                        href={route('activation')}
                        className="py-3 px-4 bg-ink text-cream font-display text-[13px] border-[2.5px] border-ink"
                        style={{ boxShadow: '3px 3px 0 var(--c-yel)' }}
                    >
                        ACTIVAR CUENTA →
                    </Link>
                )}
            </div>

            <TabBar active="matches" />
        </>
    );
}
