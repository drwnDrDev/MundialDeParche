import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Special({ special, teams, players }) {
    const { data, setData, post, processing, errors } = useForm({
        champion_team_id:     special?.champion_team_id    ?? '',
        runner_up_team_id:    special?.runner_up_team_id   ?? '',
        top_scorer_player_id: special?.top_scorer_player_id ?? '',
    });

    const isLocked = special?.is_locked ?? false;

    function handleSubmit(e) {
        e.preventDefault();
        post(route('predictions.special.save'));
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Predicciones Especiales</h2>}>
            <Head title="Predicciones Especiales" />
            <div className="py-12">
                <div className="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white shadow rounded-lg p-6">
                        {isLocked && (
                            <p className="mb-4 text-sm text-red-600 font-medium">
                                Tus predicciones especiales están bloqueadas.
                            </p>
                        )}
                        <form onSubmit={handleSubmit} className="space-y-5">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Campeón (30 pts)</label>
                                <select
                                    value={data.champion_team_id}
                                    onChange={e => setData('champion_team_id', e.target.value)}
                                    disabled={isLocked}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">— Seleccionar equipo —</option>
                                    {teams.map(t => (
                                        <option key={t.id} value={t.id}>
                                            {t.name} ({t.group?.name ?? '?'})
                                        </option>
                                    ))}
                                </select>
                                {errors.champion_team_id && <p className="mt-1 text-xs text-red-600">{errors.champion_team_id}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Sub-campeón (10 pts)</label>
                                <select
                                    value={data.runner_up_team_id}
                                    onChange={e => setData('runner_up_team_id', e.target.value)}
                                    disabled={isLocked}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">— Seleccionar equipo —</option>
                                    {teams.map(t => (
                                        <option key={t.id} value={t.id}>
                                            {t.name} ({t.group?.name ?? '?'})
                                        </option>
                                    ))}
                                </select>
                                {errors.runner_up_team_id && <p className="mt-1 text-xs text-red-600">{errors.runner_up_team_id}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700">Goleador (15 pts)</label>
                                <select
                                    value={data.top_scorer_player_id}
                                    onChange={e => setData('top_scorer_player_id', e.target.value)}
                                    disabled={isLocked}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">— Seleccionar jugador —</option>
                                    {players.map(p => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} ({p.team?.name ?? '?'})
                                        </option>
                                    ))}
                                </select>
                                {errors.top_scorer_player_id && <p className="mt-1 text-xs text-red-600">{errors.top_scorer_player_id}</p>}
                            </div>

                            {!isLocked && (
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full inline-flex justify-center py-2 px-4 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    Guardar predicciones especiales
                                </button>
                            )}
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
