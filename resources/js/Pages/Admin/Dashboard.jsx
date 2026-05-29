import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

const STATUS_COLORS = {
    finished:    'text-blue-600',
    in_progress: 'text-green-600',
    scheduled:   'text-gray-400',
};

export default function Dashboard({ stats, activeRound, pendingFixtures, recentlyUpdated }) {
    return (
        <AdminLayout header="Dashboard">
            <Head title="Admin Dashboard" />

            {/* Active round banner */}
            {activeRound ? (
                <div className="mb-5 flex items-center justify-between rounded-lg bg-green-50 border border-green-200 px-4 py-3">
                    <div>
                        <div className="text-xs font-medium text-green-600 tracking-wide">FASE ACTIVA</div>
                        <div className="text-sm font-semibold text-gray-900 mt-0.5">{activeRound.name}</div>
                        {pendingFixtures > 0 && (
                            <div className="text-xs text-gray-500 mt-0.5">{pendingFixtures} partidos sin resultado</div>
                        )}
                    </div>
                    <Link
                        href={route('admin.score-entry', { round_id: activeRound.id })}
                        className="rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Meter Scores →
                    </Link>
                </div>
            ) : (
                <div className="mb-5 rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-500">
                    No hay fases abiertas.
                    <Link href={route('admin.rounds.index')} className="ml-2 text-indigo-600 hover:underline">
                        Gestionar rondas →
                    </Link>
                </div>
            )}

            {/* Stats grid */}
            <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                {[
                    { label: 'Equipos',      value: stats.teams     },
                    { label: 'Partidos',     value: stats.fixtures  },
                    { label: 'Usuarios',     value: stats.users     },
                    { label: 'Pozo (coins)', value: stats.pot       },
                ].map(({ label, value }) => (
                    <div key={label} className="rounded-lg bg-white p-4 shadow text-center">
                        <div className="text-2xl font-bold text-gray-900">{value}</div>
                        <div className="mt-0.5 text-xs text-gray-500">{label}</div>
                    </div>
                ))}
            </div>

            {/* Alert: users pending activation */}
            {stats.notActivated > 0 && (
                <div className="mb-5 flex items-center justify-between rounded-lg bg-yellow-50 border border-yellow-200 px-4 py-3">
                    <div className="text-sm text-yellow-800">
                        <b>{stats.notActivated}</b> usuario{stats.notActivated > 1 ? 's' : ''} activo{stats.notActivated > 1 ? 's' : ''} sin activar en el pozo
                    </div>
                    <Link href={route('admin.users.index')} className="text-xs text-indigo-600 hover:underline">
                        Ver usuarios →
                    </Link>
                </div>
            )}

            {/* Recent score updates */}
            {recentlyUpdated.length > 0 && (
                <div className="rounded-lg bg-white shadow overflow-hidden">
                    <div className="px-4 py-3 border-b border-gray-100">
                        <h3 className="text-sm font-semibold text-gray-700">Últimas actualizaciones</h3>
                    </div>
                    <ul className="divide-y divide-gray-100">
                        {recentlyUpdated.map(f => (
                            <li key={f.match_number} className="flex items-center justify-between px-4 py-2.5">
                                <div className="text-sm">
                                    <span className="font-mono text-xs text-gray-400 mr-2">M{f.match_number}</span>
                                    <span className="font-medium">{f.home}</span>
                                    <span className="mx-1.5 font-bold text-gray-700">{f.home_score}–{f.away_score}</span>
                                    <span className="font-medium">{f.away}</span>
                                </div>
                                <span className={`text-xs font-medium ${STATUS_COLORS[f.status] ?? 'text-gray-400'}`}>
                                    {f.status === 'finished' ? 'FIN' : f.status === 'in_progress' ? 'VIVO' : '–'}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </AdminLayout>
    );
}
