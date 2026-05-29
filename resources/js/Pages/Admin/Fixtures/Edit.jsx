import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ fixture, rounds, groups, teams }) {
    const { data, setData, patch, processing, errors } = useForm({
        round_id:           fixture.round_id,
        group_id:           fixture.group_id ?? '',
        match_number:       fixture.match_number,
        match_date:         fixture.match_date ? fixture.match_date.slice(0, 16) : '',
        home_team_id:       fixture.home_team_id ?? '',
        away_team_id:       fixture.away_team_id ?? '',
        home_placeholder:   fixture.home_placeholder ?? '',
        away_placeholder:   fixture.away_placeholder ?? '',
        home_score:         fixture.home_score ?? '',
        away_score:         fixture.away_score ?? '',
        winner_team_id:     fixture.winner_team_id ?? '',
        went_to_extra_time: fixture.went_to_extra_time ?? false,
        status:             fixture.status,
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('admin.fixtures.update', fixture.id));
    };

    return (
        <AdminLayout header={`Editar Partido #${fixture.match_number}`}>
            <Head title={`Editar Partido #${fixture.match_number}`} />

            <div className="max-w-2xl rounded-lg bg-white p-6 shadow">
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Ronda</label>
                            <select value={data.round_id} onChange={e => setData('round_id', Number(e.target.value))}
                                className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                {rounds.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">N° Partido</label>
                            <input type="number" value={data.match_number}
                                onChange={e => setData('match_number', e.target.value)}
                                className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            {errors.match_number && <p className="mt-1 text-xs text-red-600">{errors.match_number}</p>}
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Fecha y Hora</label>
                        <input type="datetime-local" value={data.match_date}
                            onChange={e => setData('match_date', e.target.value)}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                        {errors.match_date && <p className="mt-1 text-xs text-red-600">{errors.match_date}</p>}
                    </div>

                    <fieldset className="rounded border border-gray-200 p-4">
                        <legend className="px-2 text-sm font-medium text-gray-600">Equipos</legend>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Local</label>
                                <select value={data.home_team_id} onChange={e => setData('home_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                    <option value="">— Por definir —</option>
                                    {teams.map(t => <option key={t.id} value={t.id}>{t.name} ({t.group?.name})</option>)}
                                </select>
                                {!data.home_team_id && (
                                    <input type="text" value={data.home_placeholder}
                                        onChange={e => setData('home_placeholder', e.target.value)}
                                        placeholder="Placeholder"
                                        className="mt-2 block w-full rounded border-gray-300 text-sm shadow-sm" />
                                )}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Visitante</label>
                                <select value={data.away_team_id} onChange={e => setData('away_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                    <option value="">— Por definir —</option>
                                    {teams.map(t => <option key={t.id} value={t.id}>{t.name} ({t.group?.name})</option>)}
                                </select>
                                {!data.away_team_id && (
                                    <input type="text" value={data.away_placeholder}
                                        onChange={e => setData('away_placeholder', e.target.value)}
                                        placeholder="Placeholder"
                                        className="mt-2 block w-full rounded border-gray-300 text-sm shadow-sm" />
                                )}
                            </div>
                        </div>
                    </fieldset>

                    <fieldset className="rounded border border-gray-200 p-4">
                        <legend className="px-2 text-sm font-medium text-gray-600">Resultado</legend>
                        <div className="grid grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Goles Local (90')</label>
                                <input type="number" min="0" value={data.home_score}
                                    onChange={e => setData('home_score', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Goles Visitante (90')</label>
                                <input type="number" min="0" value={data.away_score}
                                    onChange={e => setData('away_score', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Ganador Real</label>
                                <select value={data.winner_team_id} onChange={e => setData('winner_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                    <option value="">— Ninguno —</option>
                                    {data.home_team_id && (
                                        <option value={data.home_team_id}>
                                            {teams.find(t => t.id === Number(data.home_team_id))?.name ?? 'Local'}
                                        </option>
                                    )}
                                    {data.away_team_id && (
                                        <option value={data.away_team_id}>
                                            {teams.find(t => t.id === Number(data.away_team_id))?.name ?? 'Visitante'}
                                        </option>
                                    )}
                                </select>
                            </div>
                        </div>
                        <div className="mt-3 flex items-center gap-2">
                            <input type="checkbox" id="extra_time" checked={data.went_to_extra_time}
                                onChange={e => setData('went_to_extra_time', e.target.checked)} />
                            <label htmlFor="extra_time" className="text-sm text-gray-700">Fue a tiempo extra / penales</label>
                        </div>
                    </fieldset>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Estado</label>
                        <select value={data.status} onChange={e => setData('status', e.target.value)}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                            <option value="scheduled">Programado</option>
                            <option value="in_progress">En Juego</option>
                            <option value="finished">Finalizado</option>
                        </select>
                    </div>

                    <div className="flex items-center gap-4">
                        <button type="submit" disabled={processing}
                            className="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Guardar Cambios
                        </button>
                        <Link href={route('admin.fixtures.index', { round_id: fixture.round_id })}
                            className="text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
