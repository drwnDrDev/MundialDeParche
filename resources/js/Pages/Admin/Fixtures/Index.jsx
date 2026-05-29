import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

const STATUS_LABELS = {
    scheduled:   { label: 'Programado', cls: 'bg-gray-100 text-gray-600' },
    in_progress: { label: 'En Juego',   cls: 'bg-green-100 text-green-700' },
    finished:    { label: 'Finalizado', cls: 'bg-blue-100 text-blue-700' },
};

export default function Index({ fixtures, rounds, selectedRoundId }) {
    const filterRound = (id) => router.get('/admin/fixtures', { round_id: id }, { preserveState: true });

    return (
        <AdminLayout header="Partidos">
            <Head title="Admin — Partidos" />

            <div className="mb-4 flex items-center gap-4">
                <div className="flex gap-2">
                    <button onClick={() => filterRound('')}
                        className={`rounded px-3 py-1 text-sm ${!selectedRoundId ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 shadow'}`}>
                        Todos
                    </button>
                    {rounds.map(r => (
                        <button key={r.id} onClick={() => filterRound(r.id)}
                            className={`rounded px-3 py-1 text-sm ${selectedRoundId === r.id ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 shadow'}`}>
                            {r.name}
                        </button>
                    ))}
                </div>
                <Link href={route('admin.fixtures.create')}
                    className="ml-auto rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    + Nuevo Partido
                </Link>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {['#', 'Fecha', 'Local', 'Score', 'Visitante', 'Estado', ''].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {fixtures.map(f => {
                            const { label, cls } = STATUS_LABELS[f.status] ?? STATUS_LABELS.scheduled;
                            const home = f.home_team?.name ?? f.home_placeholder ?? '—';
                            const away = f.away_team?.name ?? f.away_placeholder ?? '—';
                            return (
                                <tr key={f.id}>
                                    <td className="px-4 py-3 text-sm text-gray-500">{f.match_number}</td>
                                    <td className="px-4 py-3 text-sm text-gray-600">
                                        {f.match_date ? new Date(f.match_date).toLocaleString('es-CL') : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-sm font-medium text-gray-900">{home}</td>
                                    <td className="px-4 py-3 font-mono text-sm text-gray-900">
                                        {f.home_score !== null ? `${f.home_score} - ${f.away_score}` : '— - —'}
                                    </td>
                                    <td className="px-4 py-3 text-sm font-medium text-gray-900">{away}</td>
                                    <td className="px-4 py-3 text-sm">
                                        <span className={`rounded px-2 py-1 text-xs ${cls}`}>{label}</span>
                                    </td>
                                    <td className="px-4 py-3 text-sm">
                                        <Link href={route('admin.fixtures.edit', f.id)}
                                            className="mr-3 text-indigo-600 hover:text-indigo-800">Editar</Link>
                                        <button onClick={() => {
                                            if (confirm('¿Eliminar partido?')) router.delete(route('admin.fixtures.destroy', f.id));
                                        }} className="text-red-600 hover:text-red-800">Eliminar</button>
                                    </td>
                                </tr>
                            );
                        })}
                        {fixtures.length === 0 && (
                            <tr>
                                <td colSpan={7} className="px-4 py-8 text-center text-sm text-gray-500">
                                    No hay partidos. <Link href={route('admin.fixtures.create')} className="text-indigo-600">Crear uno</Link>.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
