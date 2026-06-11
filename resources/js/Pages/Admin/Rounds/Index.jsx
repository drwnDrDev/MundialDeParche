import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

function LockModal({ round, pending, pendingSpecials, onConfirm, onCancel }) {
    const allGood = pending.length === 0 && pendingSpecials.length === 0;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            <div className="absolute inset-0 bg-black/50" onClick={onCancel} />
            <div className="relative bg-white rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
                <h2 className="text-lg font-semibold text-gray-900 mb-1">
                    Bloquear «{round.name}»
                </h2>
                <p className="text-sm text-gray-500 mb-4">
                    Las predicciones quedarán cerradas y los partidos comenzarán.
                </p>

                {allGood && (
                    <div className="flex items-start gap-3 rounded-md bg-green-50 border border-green-200 p-3 mb-5">
                        <span className="text-green-600 text-lg leading-none">✓</span>
                        <p className="text-sm text-green-800">
                            Todos los usuarios activados confirmaron sus predicciones.
                        </p>
                    </div>
                )}

                {pending.length > 0 && (
                    <div className="rounded-md bg-yellow-50 border border-yellow-200 p-3 mb-5">
                        <p className="text-sm font-medium text-yellow-800 mb-2">
                            ⚠️ {pending.length} usuario{pending.length > 1 ? 's' : ''} sin confirmar
                        </p>
                        <ul className="text-sm text-yellow-700 space-y-0.5 max-h-40 overflow-y-auto">
                            {pending.map(u => (
                                <li key={u.id} className="flex items-center gap-1.5">
                                    <span className="w-1.5 h-1.5 rounded-full bg-yellow-500 flex-shrink-0" />
                                    {u.name}
                                </li>
                            ))}
                        </ul>
                        <p className="text-xs text-yellow-600 mt-2">
                            Sus predicciones guardadas (o ninguna) serán auto-enviadas al bloquear.
                        </p>
                    </div>
                )}

                {pendingSpecials.length > 0 && (
                    <div className="rounded-md bg-orange-50 border border-orange-200 p-3 mb-5">
                        <p className="text-sm font-medium text-orange-800 mb-2">
                            🏆 {pendingSpecials.length} usuario{pendingSpecials.length > 1 ? 's' : ''} con especiales incompletas
                        </p>
                        <ul className="text-sm text-orange-700 space-y-0.5 max-h-40 overflow-y-auto">
                            {pendingSpecials.map(u => (
                                <li key={u.id} className="flex items-center gap-1.5">
                                    <span className="w-1.5 h-1.5 rounded-full bg-orange-500 flex-shrink-0" />
                                    {u.name}
                                </li>
                            ))}
                        </ul>
                        <p className="text-xs text-orange-600 mt-2">
                            Campeón, sub-campeón o goleador sin elegir. Las especiales se bloquean junto con esta ronda.
                        </p>
                    </div>
                )}

                <div className="flex justify-end gap-2">
                    <button
                        onClick={onCancel}
                        className="px-4 py-2 text-sm rounded border border-gray-300 text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button
                        onClick={onConfirm}
                        className="px-4 py-2 text-sm rounded bg-yellow-600 text-white hover:bg-yellow-700 font-medium">
                        Bloquear igual
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function Index({ rounds }) {
    const post = (url) => router.post(url);
    const [loadingLock, setLoadingLock] = useState(null);
    const [lockModal, setLockModal] = useState(null); // { round, pending }

    const handleLock = async (round) => {
        setLoadingLock(round.id);
        try {
            const res = await fetch(route('admin.rounds.pending', round.slug));
            const { pending, pendingSpecials } = await res.json();
            setLockModal({ round, pending, pendingSpecials: pendingSpecials ?? [] });
        } catch {
            alert('Error al consultar predicciones pendientes. Intenta de nuevo.');
        } finally {
            setLoadingLock(null);
        }
    };

    const confirmLock = () => {
        post(route('admin.rounds.lock', lockModal.round.slug));
        setLockModal(null);
    };

    return (
        <AdminLayout header="Rondas">
            <Head title="Admin — Rondas" />

            {lockModal && (
                <LockModal
                    round={lockModal.round}
                    pending={lockModal.pending}
                    pendingSpecials={lockModal.pendingSpecials}
                    onConfirm={confirmLock}
                    onCancel={() => setLockModal(null)}
                />
            )}

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
                                    {round.is_finalized && round.slug === 'grupos' && (
                                        <button
                                            onClick={() => {
                                                if (confirm('¿Poblar los 16 partidos del R32 con los clasificados de grupos?')) {
                                                    post(route('admin.bracket.populate-r32'));
                                                }
                                            }}
                                            className="rounded bg-indigo-600 px-3 py-1 text-xs text-white hover:bg-indigo-700">
                                            Poblar R32
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
