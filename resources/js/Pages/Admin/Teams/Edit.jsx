import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ team, groups }) {
    const { data, setData, patch, processing, errors } = useForm({
        name:      team.name,
        fifa_code: team.fifa_code,
        flag_url:  team.flag_url ?? '',
        group_id:  team.group_id,
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('admin.teams.update', team.id));
    };

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Editar Equipo</h2>}>
            <Head title={`Editar ${team.name}`} />

            <div className="max-w-xl rounded-lg bg-white p-6 shadow">
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" value={data.name} onChange={e => setData('name', e.target.value)}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                        {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">FIFA Code</label>
                        <input type="text" maxLength={3} value={data.fifa_code}
                            onChange={e => setData('fifa_code', e.target.value.toUpperCase())}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                        {errors.fifa_code && <p className="mt-1 text-xs text-red-600">{errors.fifa_code}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Flag URL</label>
                        <input type="text" value={data.flag_url} onChange={e => setData('flag_url', e.target.value)}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm" />
                        {errors.flag_url && <p className="mt-1 text-xs text-red-600">{errors.flag_url}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Grupo</label>
                        <select value={data.group_id} onChange={e => setData('group_id', Number(e.target.value))}
                            className="mt-1 block w-full rounded border-gray-300 shadow-sm">
                            {groups.map(g => (
                                <option key={g.id} value={g.id}>Grupo {g.name}</option>
                            ))}
                        </select>
                        {errors.group_id && <p className="mt-1 text-xs text-red-600">{errors.group_id}</p>}
                    </div>

                    <div className="flex items-center gap-4">
                        <button type="submit" disabled={processing}
                            className="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Guardar
                        </button>
                        <Link href={route('admin.teams.index')} className="text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
