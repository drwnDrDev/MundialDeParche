import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

const STATUS_OPTIONS = [
    { value: 'scheduled',   label: 'Programado' },
    { value: 'in_progress', label: 'En Juego'   },
    { value: 'finished',    label: 'Finalizado'  },
];

const STATUS_COLORS = {
    scheduled:   'bg-gray-100 text-gray-600',
    in_progress: 'bg-green-100 text-green-700',
    finished:    'bg-blue-100 text-blue-700',
};

function FixtureCard({ fixture, isKnockout }) {
    const [home, setHome]     = useState(fixture.home_score ?? '');
    const [away, setAway]     = useState(fixture.away_score ?? '');
    const [winner, setWinner] = useState(fixture.winner_team_id ?? '');
    const [status, setStatus] = useState(fixture.status);
    const [saving, setSaving] = useState(false);
    const [saved, setSaved]   = useState(false);

    const homeTeam = fixture.home_team?.name ?? fixture.home_placeholder ?? 'TBD';
    const awayTeam = fixture.away_team?.name ?? fixture.away_placeholder ?? 'TBD';

    const homeScore = home === '' ? null : Number(home);
    const awayScore = away === '' ? null : Number(away);
    const isDraw    = homeScore !== null && awayScore !== null && homeScore === awayScore;
    const needsWinner = isKnockout && isDraw;

    const effectiveWinner = (() => {
        if (winner) return winner;
        if (homeScore !== null && awayScore !== null) {
            if (homeScore > awayScore) return fixture.home_team_id;
            if (awayScore > homeScore) return fixture.away_team_id;
        }
        return '';
    })();

    const canSave = homeScore !== null && awayScore !== null
        && (!needsWinner || effectiveWinner);

    const save = () => {
        if (!canSave || saving) return;
        setSaving(true);
        router.patch(
            route('admin.score-entry.update', fixture.id),
            {
                home_score:     homeScore,
                away_score:     awayScore,
                winner_team_id: effectiveWinner || null,
                status,
            },
            {
                preserveScroll: true,
                onSuccess: () => { setSaved(true); setSaving(false); setTimeout(() => setSaved(false), 2000); },
                onError:   () => setSaving(false),
            }
        );
    };

    return (
        <div className="rounded-lg bg-white shadow p-4 space-y-3">
            {/* Match header */}
            <div className="flex items-center justify-between">
                <span className="text-xs font-mono text-gray-400">M{fixture.match_number}</span>
                <select
                    value={status}
                    onChange={e => setStatus(e.target.value)}
                    className={`text-xs rounded px-2 py-0.5 border-0 font-medium ${STATUS_COLORS[status]}`}
                >
                    {STATUS_OPTIONS.map(o => (
                        <option key={o.value} value={o.value}>{o.label}</option>
                    ))}
                </select>
            </div>

            {/* Score row */}
            <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                <div className="text-right">
                    <div className="text-sm font-semibold text-gray-900 leading-tight">{homeTeam}</div>
                    {fixture.home_team?.flag_url && (
                        <img src={fixture.home_team.flag_url} alt="" className="h-4 w-6 object-cover ml-auto mt-0.5" />
                    )}
                </div>

                <div className="flex items-center gap-2">
                    <input
                        type="number" min="0" max="30"
                        value={home}
                        onChange={e => { setHome(e.target.value); setSaved(false); setWinner(''); }}
                        className="w-14 h-12 text-center text-2xl font-bold border-2 border-gray-300 rounded focus:border-indigo-500 focus:outline-none"
                        inputMode="numeric"
                    />
                    <span className="text-gray-400 font-bold">–</span>
                    <input
                        type="number" min="0" max="30"
                        value={away}
                        onChange={e => { setAway(e.target.value); setSaved(false); setWinner(''); }}
                        className="w-14 h-12 text-center text-2xl font-bold border-2 border-gray-300 rounded focus:border-indigo-500 focus:outline-none"
                        inputMode="numeric"
                    />
                </div>

                <div>
                    <div className="text-sm font-semibold text-gray-900 leading-tight">{awayTeam}</div>
                    {fixture.away_team?.flag_url && (
                        <img src={fixture.away_team.flag_url} alt="" className="h-4 w-6 object-cover mt-0.5" />
                    )}
                </div>
            </div>

            {/* Winner selector — only for knockout draws */}
            {isKnockout && homeScore !== null && awayScore !== null && (
                <div className="flex items-center gap-2 pt-1">
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

            {/* Save button */}
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
    );
}

export default function ScoreEntry({ rounds, fixtures, activeRound, selectedRoundId }) {
    const isKnockout = activeRound && activeRound.slug !== 'grupos';

    const filterRound = (id) =>
        router.get(route('admin.score-entry'), { round_id: id }, { preserveState: true });

    const pending  = fixtures.filter(f => f.home_score === null);
    const finished = fixtures.filter(f => f.home_score !== null && f.status === 'finished');

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
                    <div className="mb-3 flex items-center justify-between">
                        <p className="text-sm text-gray-500">
                            {pending.length} sin resultado · {finished.length} finalizados · {fixtures.length} total
                        </p>
                    </div>

                    {fixtures.length === 0 && (
                        <p className="text-sm text-gray-500">No hay partidos en esta fase.</p>
                    )}

                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {fixtures.map(fixture => (
                            <FixtureCard
                                key={fixture.id}
                                fixture={fixture}
                                isKnockout={isKnockout}
                            />
                        ))}
                    </div>
                </>
            )}
        </AdminLayout>
    );
}
