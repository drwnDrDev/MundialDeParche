import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ teams }) {
    const byGroup = teams.reduce((acc, team) => {
        const g = team.group?.name ?? '?';
        if (!acc[g]) acc[g] = [];
        acc[g].push(team);
        return acc;
    }, {});

    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Equipos</h2>}>
            <Head title="Admin — Equipos" />

            {Object.entries(byGroup).sort(([a], [b]) => a.localeCompare(b)).map(([group, groupTeams]) => (
                <div key={group} className="mb-6">
                    <h3 className="mb-2 text-sm font-bold uppercase tracking-wider text-gray-500">Grupo {group}</h3>
                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    {['', 'Nombre', 'FIFA Code', ''].map(h => (
                                        <th key={h} className="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {groupTeams.map(team => (
                                    <tr key={team.id}>
                                        <td className="px-4 py-2">
                                            {team.flag_url
                                                ? <img src={team.flag_url} alt={team.fifa_code} className="h-5 w-8 object-cover rounded-sm shadow-sm" />
                                                : <span className="text-gray-300">—</span>}
                                        </td>
                                        <td className="px-4 py-2 text-sm font-medium text-gray-900">{team.name}</td>
                                        <td className="px-4 py-2 text-sm text-gray-600">{team.fifa_code}</td>
                                        <td className="px-4 py-2 text-sm">
                                            <Link href={route('admin.teams.edit', team.id)}
                                                className="text-indigo-600 hover:text-indigo-800">
                                                Editar
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            ))}
        </AdminLayout>
    );
}
