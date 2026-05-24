import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';

export default function Index({ rounds }) {
    const action = (url) => router.post(url);

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Rondas</h2>}>
            <Head title="Admin — Rondas" />

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {['#', 'Ronda', 'Pts Exacto', 'Pts Resultado', 'Pts Clasificado', 'Estado', 'Acciones'].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {rounds.map((round) => (
                            <tr key={round.id}>
                                <td className="px-4 py-3 text-sm text-gray-900">{round.order}</td>
                                <td className="px-4 py-3 text-sm font-medium text-gray-900">{round.name}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{round.points_exact}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{round.points_result}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{round.points_classifier}</td>
                                <td className="px-4 py-3 text-sm">
                                    {round.is_locked
                                        ? <span className="rounded bg-red-100 px-2 py-1 text-xs text-red-700">Bloqueada</span>
                                        : round.is_open
                                            ? <span className="rounded bg-green-100 px-2 py-1 text-xs text-green-700">Abierta</span>
                                            : <span className="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600">Cerrada</span>
                                    }
                                </td>
                                <td className="flex gap-2 px-4 py-3">
                                    {!round.is_open && !round.is_locked && (
                                        <button onClick={() => action(route('admin.rounds.open', round.id))}
                                            className="rounded bg-green-600 px-3 py-1 text-xs text-white hover:bg-green-700">
                                            Abrir
                                        </button>
                                    )}
                                    {round.is_open && !round.is_locked && (
                                        <button onClick={() => action(route('admin.rounds.lock', round.id))}
                                            className="rounded bg-yellow-600 px-3 py-1 text-xs text-white hover:bg-yellow-700">
                                            Bloquear
                                        </button>
                                    )}
                                    {round.is_locked && (
                                        <button onClick={() => action(route('admin.rounds.finalize', round.id))}
                                            className="rounded bg-red-600 px-3 py-1 text-xs text-white hover:bg-red-700">
                                            Finalizar
                                        </button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
