import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ rounds }) {
    const post = (url) => router.post(url);
    const [loadingLock, setLoadingLock] = useState(null);

    const handleLock = async (round) => {
        setLoadingLock(round.id);
        try {
            const res = await fetch(route('admin.rounds.pending', round.slug));
            const { pending } = await res.json();

            let message = `¿Bloquear "${round.name}"?\n\n`;
            if (pending.length === 0) {
                message += '✓ Todos los usuarios activados han confirmado sus predicciones.';
            } else {
                const names = pending.map(u => u.name).join('\n  - ');
                message += `⚠️ ${pending.length} usuario(s) NO han confirmado:\n  - ${names}\n\nSus predicciones guardadas (o ninguna) serán auto-enviadas al bloquear.`;
            }

            if (confirm(message)) {
                post(route('admin.rounds.lock', round.slug));
            }
        } finally {
            setLoadingLock(null);
        }
    };

    return (
        <AdminLayout header="Rondas">
            <Head title="Admin — Rondas" />

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {['#', 'Ronda', 'Exacto', 'Resultado', 'Clasificado', 'Estado', 'Acciones'].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {rounds.map(round => (
                            <tr key={round.id}>
                                <td className="px-4 py-3 text-sm text-gray-500">{round.order}</td>
                                <td className="px-4 py-3 text-sm font-medium text-gray-900">{round.name}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{round.points_exact}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{round.points_result}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{round.points_classifier}</td>
                                <td className="px-4 py-3 text-sm">
                                    {round.is_finalized
                                        ? <span className="rounded bg-indigo-100 px-2 py-1 text-xs text-indigo-700">Finalizada</span>
                                        : round.is_locked
                                            ? <span className="rounded bg-red-100 px-2 py-1 text-xs text-red-700">Bloqueada</span>
                                            : round.is_open
                                                ? <span className="rounded bg-green-100 px-2 py-1 text-xs text-green-700">Abierta</span>
                                                : <span className="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600">Cerrada</span>
                                    }
                                </td>
                                <td className="flex flex-wrap gap-1 px-4 py-3">
                                    {!round.is_open && !round.is_locked && !round.is_finalized && (
                                        <button onClick={() => post(route('admin.rounds.open', round.slug))}
                                            className="rounded bg-green-600 px-3 py-1 text-xs text-white hover:bg-green-700">
                                            Abrir
                                        </button>
                                    )}
                                    {round.is_open && !round.is_locked && (
                                        <button
                                            onClick={() => handleLock(round)}
                                            disabled={loadingLock === round.id}
                                            className="rounded bg-yellow-600 px-3 py-1 text-xs text-white hover:bg-yellow-700 disabled:opacity-50">
                                            {loadingLock === round.id ? 'Verificando...' : 'Bloquear'}
                                        </button>
                                    )}
                                    {round.is_locked && !round.is_finalized && (
                                        <button
                                            onClick={() => {
                                                if (confirm(`¿Finalizar "${round.name}"? Se calcularán los puntos de clasificados.`)) {
                                                    post(route('admin.rounds.finalize', round.slug));
                                                }
                                            }}
                                            className="rounded bg-red-600 px-3 py-1 text-xs text-white hover:bg-red-700">
                                            Finalizar
                                        </button>
                                    )}
                                    {round.is_finalized && (
                                        <span className="text-xs text-gray-400">✓ Completada</span>
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
