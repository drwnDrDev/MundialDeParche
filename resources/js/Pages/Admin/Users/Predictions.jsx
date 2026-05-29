import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

export default function Predictions({ targetUser, rounds, fixtures, predictions, submissions }) {
    const [activeRoundId, setActiveRoundId] = useState(rounds[0]?.id ?? null);

    const activeRound      = rounds.find(r => r.id === activeRoundId);
    const activeFixtures   = fixtures[activeRoundId] ?? [];
    const activeSubmission = submissions[activeRoundId] ?? null;

    const ptsExact      = activeFixtures.reduce((s, f) => s + (predictions[f.id]?.pts_exact  ?? 0), 0);
    const ptsResult     = activeFixtures.reduce((s, f) => s + (predictions[f.id]?.pts_result ?? 0), 0);
    const ptsClassifier = activeSubmission?.pts_classifier ?? 0;
    const total         = ptsExact + ptsResult + ptsClassifier;

    return (
        <AdminLayout header={`Predicciones — ${targetUser.name}`}>
            <Head title={`Predicciones · ${targetUser.name}`} />

            {/* User summary bar */}
            <div className="mb-4 flex flex-wrap items-center gap-4 rounded-lg bg-white p-4 shadow">
                <div>
                    <div className="text-sm font-semibold text-gray-900">{targetUser.name}</div>
                    <div className="text-xs text-gray-500">{targetUser.email}</div>
                </div>
                <div className="flex gap-3 text-sm ml-auto flex-wrap">
                    <span className="text-gray-600">
                        <b>{targetUser.total_points}</b> pts totales
                    </span>
                    <span className={targetUser.is_activated ? 'text-green-600' : 'text-gray-400'}>
                        {targetUser.is_activated ? '✓ En pozo' : 'Sin activar'}
                    </span>
                </div>
                <Link
                    href={route('admin.users.index')}
                    className="text-xs text-indigo-600 hover:text-indigo-800"
                >
                    ← Volver
                </Link>
            </div>

            {/* Round tabs */}
            <div className="flex gap-2 flex-wrap mb-4">
                {rounds.map(r => {
                    const sub = submissions[r.id];
                    return (
                        <button
                            key={r.id}
                            onClick={() => setActiveRoundId(r.id)}
                            className={[
                                'px-3 py-1.5 rounded text-sm font-medium border-2 transition-colors',
                                activeRoundId === r.id
                                    ? 'bg-indigo-600 border-indigo-600 text-white'
                                    : 'bg-white border-gray-200 text-gray-700 hover:border-indigo-400',
                            ].join(' ')}
                        >
                            {r.name}
                            {sub && (
                                <span className="ml-1.5 text-xs opacity-70">
                                    {sub.status === 'locked' ? '🔒' : sub.status === 'submitted' ? '✓' : '✎'}
                                </span>
                            )}
                        </button>
                    );
                })}
            </div>

            {activeRound && (
                <>
                    {/* Points summary for active round */}
                    <div className="mb-4 grid grid-cols-4 gap-2">
                        {[
                            { label: 'Exacto',     value: ptsExact,      color: 'text-red-600'    },
                            { label: 'Resultado',  value: ptsResult,     color: 'text-teal-600'   },
                            { label: 'Clasif.',    value: ptsClassifier, color: 'text-yellow-600' },
                            { label: 'Total fase', value: total,         color: 'text-indigo-700 font-bold' },
                        ].map(({ label, value, color }) => (
                            <div key={label} className="rounded-lg bg-white p-3 shadow text-center">
                                <div className={`text-xl font-bold ${color}`}>{value}</div>
                                <div className="text-xs text-gray-500 mt-0.5">{label}</div>
                            </div>
                        ))}
                    </div>

                    {/* Submission status */}
                    {activeSubmission && (
                        <div className="mb-3 text-xs text-gray-500">
                            Estado: <b>{activeSubmission.status}</b>
                            {activeSubmission.submitted_at && ` · enviado ${new Date(activeSubmission.submitted_at).toLocaleString('es')}`}
                        </div>
                    )}

                    {/* Fixture rows */}
                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    {['#', 'Partido', 'Real', 'Predicción', 'Exacto', 'Result'].map(h => (
                                        <th key={h} className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            {h}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {activeFixtures.map(f => {
                                    const pred = predictions[f.id];
                                    const home = f.home_team?.name ?? f.home_placeholder ?? 'TBD';
                                    const away = f.away_team?.name ?? f.away_placeholder ?? 'TBD';
                                    const realScore = f.home_score !== null
                                        ? `${f.home_score}–${f.away_score}`
                                        : '–';
                                    const predScore = pred
                                        ? `${pred.predicted_home}–${pred.predicted_away}`
                                        : '—';

                                    return (
                                        <tr key={f.id} className={!pred ? 'opacity-40' : ''}>
                                            <td className="px-3 py-2 text-xs text-gray-400 font-mono">{f.match_number}</td>
                                            <td className="px-3 py-2 text-sm">
                                                <span className="font-medium">{home}</span>
                                                <span className="mx-1 text-gray-400">vs</span>
                                                <span className="font-medium">{away}</span>
                                            </td>
                                            <td className="px-3 py-2 font-mono text-sm text-gray-700">{realScore}</td>
                                            <td className="px-3 py-2 font-mono text-sm font-semibold">{predScore}</td>
                                            <td className="px-3 py-2 text-sm">
                                                {pred?.pts_exact > 0
                                                    ? <span className="text-green-600 font-bold">+{pred.pts_exact}</span>
                                                    : <span className="text-gray-300">—</span>
                                                }
                                            </td>
                                            <td className="px-3 py-2 text-sm">
                                                {pred?.pts_result > 0
                                                    ? <span className="text-teal-600 font-bold">+{pred.pts_result}</span>
                                                    : <span className="text-gray-300">—</span>
                                                }
                                            </td>
                                        </tr>
                                    );
                                })}
                                {activeFixtures.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-6 text-center text-sm text-gray-400">
                                            Sin partidos en esta fase.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </>
            )}
        </AdminLayout>
    );
}
