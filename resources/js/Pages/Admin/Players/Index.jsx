import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

function PlayerRow({ player, teams }) {
    const [editing, setEditing] = useState(false);
    const { data, setData, patch, processing } = useForm({
        team_id: player.team_id,
        name:    player.name,
    });

    const save = (e) => {
        e.preventDefault();
        patch(route('admin.players.update', player.id), {
            onSuccess: () => setEditing(false),
        });
    };

    if (editing) {
        return (
            <tr>
                <td className="px-4 py-2">
                    <input type="text" value={data.name} onChange={e => setData('name', e.target.value)}
                        className="w-full rounded border-gray-300 text-sm shadow-sm" />
                </td>
                <td className="px-4 py-2">
                    <select value={data.team_id} onChange={e => setData('team_id', Number(e.target.value))}
                        className="w-full rounded border-gray-300 text-sm shadow-sm">
                        {teams.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                    </select>
                </td>
                <td className="px-4 py-2 flex gap-2">
                    <button onClick={save} disabled={processing}
                        className="rounded bg-green-600 px-2 py-1 text-xs text-white hover:bg-green-700">Guardar</button>
                    <button onClick={() => setEditing(false)}
                        className="rounded bg-gray-200 px-2 py-1 text-xs text-gray-700 hover:bg-gray-300">Cancelar</button>
                </td>
            </tr>
        );
    }

    return (
        <tr>
            <td className="px-4 py-2 text-sm text-gray-900">{player.name}</td>
            <td className="px-4 py-2 text-sm text-gray-600">{player.team?.name ?? '—'}</td>
            <td className="px-4 py-2 flex gap-2">
                <button onClick={() => setEditing(true)}
                    className="text-indigo-600 hover:text-indigo-800 text-sm">Editar</button>
                <button onClick={() => {
                    if (confirm('¿Eliminar jugador?')) router.delete(route('admin.players.destroy', player.id));
                }} className="text-red-600 hover:text-red-800 text-sm">Eliminar</button>
            </td>
        </tr>
    );
}

export default function Index({ players, teams }) {
    const { data, setData, post, processing, reset, errors } = useForm({ team_id: '', name: '' });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.players.store'), { onSuccess: () => reset() });
    };

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Jugadores</h2>}>
            <Head title="Admin — Jugadores" />

            <div className="mb-6 max-w-lg rounded-lg bg-white p-4 shadow">
                <h3 className="mb-3 text-sm font-medium text-gray-700">Agregar Jugador</h3>
                <form onSubmit={submit} className="flex gap-3">
                    <select value={data.team_id} onChange={e => setData('team_id', e.target.value)}
                        className="flex-1 rounded border-gray-300 text-sm shadow-sm">
                        <option value="">Equipo…</option>
                        {teams.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                    </select>
                    <input type="text" value={data.name} onChange={e => setData('name', e.target.value)}
                        placeholder="Nombre del jugador"
                        className="flex-1 rounded border-gray-300 text-sm shadow-sm" />
                    <button type="submit" disabled={processing}
                        className="rounded bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-700 disabled:opacity-50">
                        Agregar
                    </button>
                </form>
                {(errors.team_id || errors.name) && (
                    <p className="mt-2 text-xs text-red-600">{errors.team_id ?? errors.name}</p>
                )}
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {['Jugador', 'Equipo', 'Acciones'].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {players.map(p => (
                            <PlayerRow key={p.id} player={p} teams={teams} />
                        ))}
                        {players.length === 0 && (
                            <tr>
                                <td colSpan={3} className="px-4 py-8 text-center text-sm text-gray-500">
                                    No hay jugadores registrados.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
