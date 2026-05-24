import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

function CreateUserForm() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name:                  '',
        email:                 '',
        password:              '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.users.store'), { onSuccess: () => reset() });
    };

    return (
        <div className="mb-6 max-w-lg rounded-lg bg-white p-4 shadow">
            <h3 className="mb-3 text-sm font-medium text-gray-700">Crear Usuario</h3>
            <form onSubmit={submit} className="space-y-3">
                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <input type="text" value={data.name} onChange={e => setData('name', e.target.value)}
                            placeholder="Nombre" className="w-full rounded border-gray-300 text-sm shadow-sm" />
                        {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                    </div>
                    <div>
                        <input type="email" value={data.email} onChange={e => setData('email', e.target.value)}
                            placeholder="Email" className="w-full rounded border-gray-300 text-sm shadow-sm" />
                        {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email}</p>}
                    </div>
                    <div>
                        <input type="password" value={data.password} onChange={e => setData('password', e.target.value)}
                            placeholder="Contraseña" className="w-full rounded border-gray-300 text-sm shadow-sm" />
                        {errors.password && <p className="mt-1 text-xs text-red-600">{errors.password}</p>}
                    </div>
                    <div>
                        <input type="password" value={data.password_confirmation}
                            onChange={e => setData('password_confirmation', e.target.value)}
                            placeholder="Confirmar contraseña" className="w-full rounded border-gray-300 text-sm shadow-sm" />
                    </div>
                </div>
                <button type="submit" disabled={processing}
                    className="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                    Crear Usuario
                </button>
            </form>
        </div>
    );
}

function ReopenModal({ user, rounds, onClose }) {
    const { data, setData, post, processing } = useForm({ round_id: '' });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.users.reopen-predictions', user.id), { onSuccess: onClose });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div className="w-96 rounded-lg bg-white p-6 shadow-xl">
                <h3 className="mb-4 text-base font-semibold text-gray-800">Reabrir predicciones — {user.name}</h3>
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Ronda</label>
                        <select value={data.round_id} onChange={e => setData('round_id', e.target.value)}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                            <option value="">Seleccionar ronda…</option>
                            {rounds.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
                        </select>
                    </div>
                    <div className="flex gap-3">
                        <button type="submit" disabled={processing || !data.round_id}
                            className="rounded bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 disabled:opacity-50">
                            Reabrir
                        </button>
                        <button type="button" onClick={onClose}
                            className="rounded bg-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-300">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export default function Index({ users, rounds }) {
    const [reopenTarget, setReopenTarget] = useState(null);

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Usuarios</h2>}>
            <Head title="Admin — Usuarios" />

            <CreateUserForm />

            {reopenTarget && (
                <ReopenModal user={reopenTarget} rounds={rounds} onClose={() => setReopenTarget(null)} />
            )}

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            {['Nombre', 'Email', 'Acceso', 'Pozo', 'Coins', 'Pts', 'Acciones'].map(h => (
                                <th key={h} className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {users.map(user => (
                            <tr key={user.id}>
                                <td className="px-4 py-3 text-sm font-medium text-gray-900">{user.name}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{user.email}</td>
                                <td className="px-4 py-3 text-sm">
                                    <span className={`rounded px-2 py-1 text-xs ${user.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                        {user.is_active ? 'Activo' : 'Inactivo'}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-sm">
                                    <span className={`rounded px-2 py-1 text-xs ${user.is_activated ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500'}`}>
                                        {user.is_activated ? 'En pozo' : 'Sin activar'}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-sm text-gray-600">{user.coins_balance}</td>
                                <td className="px-4 py-3 text-sm text-gray-600">{user.total_points}</td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-1">
                                        <button onClick={() => router.post(route('admin.users.toggle-active', user.id))}
                                            className={`rounded px-2 py-1 text-xs text-white ${user.is_active ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'}`}>
                                            {user.is_active ? 'Desactivar' : 'Activar'}
                                        </button>
                                        {!user.is_activated ? (
                                            <button onClick={() => router.post(route('admin.users.activate-pot', user.id))}
                                                className="rounded bg-yellow-500 px-2 py-1 text-xs text-white hover:bg-yellow-600">
                                                + Pozo
                                            </button>
                                        ) : (
                                            <button onClick={() => router.post(route('admin.users.deactivate-pot', user.id))}
                                                className="rounded bg-gray-400 px-2 py-1 text-xs text-white hover:bg-gray-500">
                                                − Pozo
                                            </button>
                                        )}
                                        <button onClick={() => setReopenTarget(user)}
                                            className="rounded bg-orange-500 px-2 py-1 text-xs text-white hover:bg-orange-600">
                                            Reabrir
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {users.length === 0 && (
                            <tr>
                                <td colSpan={7} className="px-4 py-8 text-center text-sm text-gray-500">
                                    No hay usuarios registrados.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
