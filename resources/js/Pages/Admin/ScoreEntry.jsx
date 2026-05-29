import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const STATUS_OPTIONS = [
    { value: 'scheduled',   label: 'Programado' },
    { value: 'in_progress', label: 'En Juego'   },
    { value: 'finished',    label: 'Finalizado'  },
];

function ActiveFixtureRow({ fixture, isKnockout }) {
    const [home, setHome]     = useState(fixture.home_score ?? '');
    const [away, setAway]     = useState(fixture.away_score ?? '');
    const [winner, setWinner] = useState(fixture.winner_team_id ?? '');
    const [status, setStatus] = useState(fixture.status);
    const [saving, setSaving] = useState(false);
    const [saved, setSaved]   = useState(false);

    const homeTeam = fixture.home_team?.name ?? fixture.home_placeholder ?? 'TBD';
    const awayTeam = fixture.away_team?.name ?? fixture.away_placeholder ?? 'TBD';

    const homeScore  = home === '' ? null : Number(home);
    const awayScore  = away === '' ? null : Number(away);
    const isDraw     = homeScore !== null && awayScore !== null && homeScore === awayScore;
    const needsWinner = isKnockout && isDraw;

    const effectiveWinner = (() => {
        if (winner) return winner;
        if (homeScore !== null && awayScore !== null) {
            if (homeScore > awayScore) return fixture.home_team_id;
            if (awayScore > homeScore) return fixture.away_team_id;
        }
        return '';
    })();

    const canSave = homeScore !== null && awayScore !== null && (!needsWinner || effectiveWinner);

    const save = () => {
        if (!canSave || saving) return;
        setSaving(true);
        router.patch(
            route('admin.score-entry.update', fixture.id),
            { home_score: homeScore, away_score: awayScore, winner_team_id: effectiveWinner || null, status },
            {
                preserveScroll: true,
                onSuccess: () => { setSaved(true); setSaving(false); setTimeout(() => setSaved(false), 2500); },
                onError:   () => setSaving(false),
            }
        );
    };

    const isLive = status === 'in_progress';

    return (
        <div className={[
            'rounded-lg bg-white shadow border-l-4 p-4',
            isLive ? 'border-green-500' : 'border-transparent',
        ].join(' ')}>
            <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                {/* Match info */}
                <div className="flex items-center gap-2 sm:w-28 flex-shrink-0">
                    <span className="text-xs font-mono text-gray-400 w-8">M{fixture.match_number}</span>
                    <select
                        value={status}
                        onChange={e => { setStatus(e.target.value); setSaved(false); }}
                        className={[
                            'text-xs rounded px-1.5 py-0.5 border font-medium flex-1',
                            isLive ? 'bg-green-100 text-green-700 border-green-300' : 'bg-gray-100 text-gray-600 border-gray-200',
                        ].join(' ')}
                    >
                        {STATUS_OPTIONS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
                    </select>
                </div>

                {/* Score row */}
                <div className="flex items-center gap-3 flex-1">
                    <div className="flex-1 text-right">
                        <p className="text-sm font-semibold text-gray-900 truncate">{homeTeam}</p>
                        {fixture.home_team?.flag_url && (
                            <img src={fixture.home_team.flag_url} alt="" className="h-4 w-6 object-cover ml-auto mt-0.5" />
                        )}
                    </div>

                    <div className="flex items-center gap-1.5">
                        <input
                            type="number" min="0" max="30"
                            value={home}
                            onChange={e => { setHome(e.target.value); setSaved(false); setWinner(''); }}
                            className="w-14 h-12 text-center text-2xl font-bold border-2 border-gray-300 rounded focus:border-indigo-500 focus:outline-none"
                            inputMode="numeric"
                        />
                        <span className="text-gray-400 font-bold text-lg">–</span>
                        <input
                            type="number" min="0" max="30"
                            value={away}
                            onChange={e => { setAway(e.target.value); setSaved(false); setWinner(''); }}
                            className="w-14 h-12 text-center text-2xl font-bold border-2 border-gray-300 rounded focus:border-indigo-500 focus:outline-none"
                            inputMode="numeric"
                        />
                    </div>

                    <div className="flex-1">
                        <p className="text-sm font-semibold text-gray-900 truncate">{awayTeam}</p>
                        {fixture.away_team?.flag_url && (
                            <img src={fixture.away_team.flag_url} alt="" className="h-4 w-6 object-cover mt-0.5" />
                        )}
                    </div>
                </div>

                {/* Save button */}
                <div className="sm:w-28 flex-shrink-0">
                    <button
                        onClick={save}
                        disabled={!canSave || saving}
                        className={[
                            'w-full py-2.5 rounded text-sm font-medium transition-colors',
                            saved
                                ? 'bg-green-500 text-white'
                                : canSave
                                    ? 'bg-indigo-600 hover:bg-indigo-700 text-white'
                                    : 'bg-gray-100 text-gray-400 cursor-not-allowed',
                        ].join(' ')}
                    >
                        {saved ? '✓ Guardado' : saving ? 'Guardando…' : 'Guardar'}
                    </button>
                </div>
            </div>

            {/* Knockout winner selector — only when draw */}
            {isKnockout && isDraw && homeScore !== null && (
                <div className="mt-3 flex items-center gap-2 pt-2 border-t border-gray-100">
                    <span className="text-xs text-gray-500 flex-shrink-0">Ganador (ET/Pen):</span>
                    <div className="flex gap-2 flex-1">
                        {[
                            { id: fixture.home_team_id, label: homeTeam },
                            { id: fixture.away_team_id, label: awayTeam },
                        ].filter(o => o.id).map(option => (
                            <button
                                key={option.id}
                                type="button"
                                onClick={() => setWinner(String(option.id))}
                                className={[
                                    'flex-1 py-1.5 text-xs rounded border-2 font-medium transition-colors',
                                    String(effectiveWinner) === String(option.id)
                                        ? 'bg-indigo-600 border-indigo-600 text-white'
                                        : 'bg-white border-gray-200 text-gray-700 hover:border-indigo-400',
                                ].join(' ')}
                            >
                                {option.label}
                            </button>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

function FinishedFixtureRow({ fixture }) {
    const homeTeam = fixture.home_team?.name ?? fixture.home_placeholder ?? 'TBD';
    const awayTeam = fixture.away_team?.name ?? fixture.away_placeholder ?? 'TBD';

    return (
        <div className="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 flex items-center gap-3">
            <span className="text-xs font-mono text-slate-400 w-8 flex-shrink-0">M{fixture.match_number}</span>

            <div className="flex items-center gap-2 flex-1 min-w-0">
                <p className="flex-1 text-right text-sm text-slate-600 truncate">{homeTeam}</p>
                <p className="font-mono text-sm font-bold text-slate-700 flex-shrink-0">
                    {fixture.home_score} – {fixture.away_score}
                </p>
                <p className="flex-1 text-sm text-slate-600 truncate">{awayTeam}</p>
            </div>

            <span className="rounded bg-blue-100 px-2 py-0.5 text-xs text-blue-700 flex-shrink-0">Finalizado</span>

            <Link
                href={route('admin.fixtures.edit', fixture.id)}
                className="text-xs text-indigo-600 hover:text-indigo-800 flex-shrink-0"
            >
                Corregir →
            </Link>
        </div>
    );
}

export default function ScoreEntry({ rounds, fixtures, activeRound, selectedRoundId }) {
    const isKnockout = activeRound && activeRound.slug !== 'grupos';

    const filterRound = (id) =>
        router.get(route('admin.score-entry'), { round_id: id }, { preserveState: true });

    const active   = fixtures.filter(f => f.status !== 'finished');
    const finished = fixtures.filter(f => f.status === 'finished');

    return (
        <AdminLayout header="Score Entry">
            <Head title="Admin — Score Entry" />

            {/* Round tabs */}
            <div className="flex gap-2 flex-wrap mb-5">
                {rounds.map(r => (
                    <button
                        key={r.id}
                        onClick={() => filterRound(r.id)}
                        className={[
                            'px-3 py-1.5 rounded text-sm font-medium border-2 transition-colors',
                            selectedRoundId === r.id
                                ? 'bg-indigo-600 border-indigo-600 text-white'
                                : 'bg-white border-gray-200 text-gray-700 hover:border-indigo-400',
                        ].join(' ')}
                    >
                        {r.name}
                        {r.is_open && <span className="ml-1.5 text-xs opacity-70">●</span>}
                    </button>
                ))}
            </div>

            {rounds.length === 0 && (
                <p className="text-sm text-gray-500">No hay fases abiertas o bloqueadas aún.</p>
            )}

            {activeRound && (
                <>
                    <p className="text-sm text-gray-500 mb-4">
                        {active.length} pendientes · {finished.length} finalizados · {fixtures.length} total
                    </p>

                    {fixtures.length === 0 && (
                        <p className="text-sm text-gray-500">No hay partidos en esta fase.</p>
                    )}

                    {/* Active fixtures */}
                    <div className="space-y-3">
                        {active.map(fixture => (
                            <ActiveFixtureRow
                                key={fixture.id}
                                fixture={fixture}
                                isKnockout={isKnockout}
                            />
                        ))}
                    </div>

                    {/* Finished fixtures section */}
                    {finished.length > 0 && (
                        <>
                            <div className="my-5 flex items-center gap-3">
                                <div className="h-px flex-1 bg-slate-200" />
                                <span className="text-xs text-slate-400 font-medium">
                                    — Finalizados ({finished.length}) —
                                </span>
                                <div className="h-px flex-1 bg-slate-200" />
                            </div>
                            <div className="space-y-2">
                                {finished.map(fixture => (
                                    <FinishedFixtureRow key={fixture.id} fixture={fixture} />
                                ))}
                            </div>
                        </>
                    )}
                </>
            )}
        </AdminLayout>
    );
}
