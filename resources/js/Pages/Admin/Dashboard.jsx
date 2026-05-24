import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard({ stats }) {
    return (
        <AdminLayout header={<h2 className="text-xl font-semibold text-gray-800">Dashboard</h2>}>
            <Head title="Admin Dashboard" />

            <div className="grid grid-cols-2 gap-6 sm:grid-cols-5">
                {[
                    { label: 'Equipos', value: stats.teams },
                    { label: 'Partidos', value: stats.fixtures },
                    { label: 'Rondas', value: stats.rounds },
                    { label: 'Usuarios', value: stats.users },
                    { label: 'Pozo (coins)', value: stats.pot },
                ].map(({ label, value }) => (
                    <div key={label} className="rounded-lg bg-white p-6 shadow text-center">
                        <p className="text-3xl font-bold text-gray-900">{value}</p>
                        <p className="mt-1 text-sm text-gray-500">{label}</p>
                    </div>
                ))}
            </div>
        </AdminLayout>
    );
}
