import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Tournament({ teams, players, derivedChampion, derivedRunnerUp, savedResults }) {
    const isFinalized = !!savedResults;

    const { data, setData, post, processing, errors } = useForm({
        champion_team_id:     derivedChampion?.id?.toString()  ?? '',
        runner_up_team_id:    derivedRunnerUp?.id?.toString()  ?? '',
        top_scorer_player_id: '',
    });

    const allFilled = data.champion_team_id && data.runner_up_team_id && data.top_scorer_player_id;

    function handleSubmit(e) {
        e.preventDefault();
        if (!confirm('¿Finalizar torneo? Esta acción calculará los puntos especiales de todos los usuarios.')) return;
        post(route('admin.tournament.finalize'));
    }

    return (
        <AdminLayout header="Torneo">
            <Head title="Finalizar Torneo" />
            <div className="py-12">
                <div className="mx-auto max-w-xl px-4 sm:px-6 lg:px-8 space-y-5">

                    {/* Banner: ya finalizado */}
                    {isFinalized && (
                        <div className="bg-green-50 border border-green-300 rounded-lg p-4">
                            <div className="text-sm font-semibold text-green-800 mb-2">✓ TORNEO FINALIZADO</div>
                            <div className="text-xs text-green-700 space-y-1">
                                <div>Campeón ID: {savedResults.champion_team_id}</div>
                                <div>Sub-campeón ID: {savedResults.runner_up_team_id}</div>
                                <div>Goleador ID: {savedResults.top_scorer_player_id}</div>
                            </div>
                        </div>
                    )}

                    {/* Banner: campeón detectado del bracket */}
                    {!isFinalized && derivedChampion && (
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div className="text-xs font-semibold text-blue-700 mb-1">DETECTADO DEL BRACKET (M104)</div>
                            <div className="text-sm text-blue-800">
                                🏆 Campeón: <b>{derivedChampion.name}</b> · Sub-campeón: <b>{derivedRunnerUp?.name ?? '—'}</b>
                            </div>
                            <div className="text-xs text-blue-600 mt-1">Puedes cambiarlos manualmente si es necesario.</div>
                        </div>
                    )}

                    <div className="bg-white shadow rounded-lg p-6 space-y-5">
                        <p className="text-sm text-gray-600">
                            Confirma el campeón, sub-campeón y goleador real del torneo. Los puntos especiales de todos
                            los usuarios se calcularán al finalizar.
                        </p>

                        <form onSubmit={handleSubmit} className="space-y-5">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Campeón</label>
                                <select
                                    value={data.champion_team_id}
                                    onChange={e => setData('champion_team_id', e.target.value)}
                                    disabled={isFinalized}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm disabled:bg-gray-100"
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
                                    disabled={isFinalized}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm disabled:bg-gray-100"
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
                                    disabled={isFinalized}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm disabled:bg-gray-100"
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

                            {!isFinalized && (
                                <button
                                    type="submit"
                                    disabled={processing || !allFilled}
                                    className="w-full py-2 px-4 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 disabled:opacity-50"
                                >
                                    Finalizar torneo y calcular puntos
                                </button>
                            )}
                        </form>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
