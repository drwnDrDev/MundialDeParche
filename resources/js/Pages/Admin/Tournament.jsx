import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Tournament({ teams, players }) {
    const { data, setData, post, processing, errors } = useForm({
        champion_team_id:     '',
        runner_up_team_id:    '',
        top_scorer_player_id: '',
    });

    function handleSubmit(e) {
        e.preventDefault();
        if (!confirm('¿Finalizar torneo? Esta acción calculará los puntos especiales de todos los usuarios.')) return;
        post(route('admin.tournament.finalize'));
    }

    return (
        <AdminLayout header="Torneo">
            <Head title="Finalizar Torneo" />
            <div className="py-12">
                <div className="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white shadow rounded-lg p-6 space-y-5">
                        <p className="text-sm text-gray-600">
                            Ingresá el campeón, sub-campeón y goleador real del torneo. Los puntos especiales de todos
                            los usuarios se calcularán y las predicciones quedarán bloqueadas.
                        </p>

                        <form onSubmit={handleSubmit} className="space-y-5">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Campeón</label>
                                <select
                                    value={data.champion_team_id}
                                    onChange={e => setData('champion_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"
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
                                <label className="block text-sm font-medium text-gray-700">Sub-campeón</label>
                                <select
                                    value={data.runner_up_team_id}
                                    onChange={e => setData('runner_up_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"
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
                                <label className="block text-sm font-medium text-gray-700">Goleador</label>
                                <select
                                    value={data.top_scorer_player_id}
                                    onChange={e => setData('top_scorer_player_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"
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

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-2 px-4 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 disabled:opacity-50"
                            >
                                Finalizar torneo y calcular puntos
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
