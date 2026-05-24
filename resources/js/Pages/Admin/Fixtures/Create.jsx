import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ rounds, groups, teams }) {
    const { data, setData, post, processing, errors } = useForm({
        round_id:         '',
        group_id:         '',
        match_number:     '',
        match_date:       '',
        home_team_id:     '',
        away_team_id:     '',
        home_placeholder: '',
        away_placeholder: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.fixtures.store'));
    };

    const selectedRound = rounds.find(r => r.id === Number(data.round_id));
    const isGroupStage  = selectedRound?.slug === 'grupos';
    const groupTeams    = data.group_id ? teams.filter(t => t.group_id === Number(data.group_id)) : teams;

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Nuevo Partido</h2>}>
            <Head title="Admin — Nuevo Partido" />

            <div className="max-w-xl rounded-lg bg-white p-6 shadow">
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Ronda</label>
                        <select value={data.round_id} onChange={e => setData('round_id', e.target.value)}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                            <option value="">Seleccionar ronda…</option>
                            {rounds.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
                        </select>
                        {errors.round_id && <p className="mt-1 text-xs text-red-600">{errors.round_id}</p>}
                    </div>

                    {isGroupStage && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Grupo</label>
                            <select value={data.group_id} onChange={e => setData('group_id', e.target.value)}
                                className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                <option value="">Seleccionar grupo…</option>
                                {groups.map(g => <option key={g.id} value={g.id}>Grupo {g.name}</option>)}
                            </select>
                            {errors.group_id && <p className="mt-1 text-xs text-red-600">{errors.group_id}</p>}
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">N° Partido</label>
                            <input type="number" min="1" max="104" value={data.match_number}
                                onChange={e => setData('match_number', e.target.value)}
                                className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            {errors.match_number && <p className="mt-1 text-xs text-red-600">{errors.match_number}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Fecha y Hora</label>
                            <input type="datetime-local" value={data.match_date}
                                onChange={e => setData('match_date', e.target.value)}
                                className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            {errors.match_date && <p className="mt-1 text-xs text-red-600">{errors.match_date}</p>}
                        </div>
                    </div>

                    {isGroupStage ? (
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Local</label>
                                <select value={data.home_team_id} onChange={e => setData('home_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                    <option value="">— Equipo —</option>
                                    {groupTeams.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Visitante</label>
                                <select value={data.away_team_id} onChange={e => setData('away_team_id', e.target.value)}
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                                    <option value="">— Equipo —</option>
                                    {groupTeams.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                                </select>
                            </div>
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Placeholder Local</label>
                                <input type="text" value={data.home_placeholder}
                                    onChange={e => setData('home_placeholder', e.target.value)}
                                    placeholder="ej. Ganador Grupo A"
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Placeholder Visitante</label>
                                <input type="text" value={data.away_placeholder}
                                    onChange={e => setData('away_placeholder', e.target.value)}
                                    placeholder="ej. 2do Grupo B"
                                    className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                            </div>
                        </div>
                    )}

                    <div className="flex items-center gap-4">
                        <button type="submit" disabled={processing}
                            className="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Crear Partido
                        </button>
                        <Link href={route('admin.fixtures.index')} className="text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
