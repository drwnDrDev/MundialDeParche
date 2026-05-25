import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Ranking({ users: initialUsers }) {
    const { auth } = usePage().props;
    const [users, setUsers] = useState(initialUsers);

    useEffect(() => {
        const channel = window.Echo.join('quinela');

        channel.listen('.PointsUpdated', (event) => {
            setUsers((prev) => {
                const updated = prev.map((u) =>
                    u.id === event.user_id
                        ? { ...u, total_points: event.total_points }
                        : u
                );
                const sorted = [...updated].sort((a, b) => b.total_points - a.total_points);
                return sorted.map((u, i) => ({ ...u, position: i + 1 }));
            });
        });

        return () => {
            window.Echo.leave('quinela');
        };
    }, []);

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Ranking</h2>}>
            <Head title="Ranking" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div className="bg-white shadow rounded-lg overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jugador</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Puntos</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {users.map((user) => (
                                    <tr
                                        key={user.id}
                                        className={user.id === auth.user.id ? 'bg-indigo-50' : ''}
                                    >
                                        <td className="px-4 py-3 text-sm font-bold text-gray-700">
                                            {user.position}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-800">
                                            {user.name}
                                            {user.id === auth.user.id && (
                                                <span className="ml-2 text-xs text-indigo-500">(vos)</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-sm font-semibold text-right text-indigo-700">
                                            {user.total_points}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
