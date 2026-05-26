import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

const STATUS_LABELS = {
    draft:     { label: 'Borrador',    className: 'bg-yellow-100 text-yellow-800' },
    submitted: { label: 'Confirmado',  className: 'bg-green-100 text-green-800' },
    locked:    { label: 'Bloqueado',   className: 'bg-red-100 text-red-800' },
};

export default function Index({ rounds, submissions }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Mis Predicciones</h2>}>
            <Head title="Predicciones" />
            <div className="py-12">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-4">
                    {rounds.map((round) => {
                        const submission = submissions[round.id];
                        const status     = submission?.status;
                        const badge      = STATUS_LABELS[status];
                        const canPredict = round.is_open && status !== 'locked';

                        return (
                            <div key={round.id} className="bg-white shadow rounded-lg p-5 flex items-center justify-between">
                                <div>
                                    <h3 className="font-semibold text-gray-900">{round.name}</h3>
                                    <div className="mt-1 flex items-center gap-2 text-sm text-gray-500">
                                        {round.is_locked && <span className="text-red-600">Cerrada</span>}
                                        {round.is_open && !round.is_locked && <span className="text-green-600">Abierta</span>}
                                        {!round.is_open && !round.is_locked && <span>No disponible</span>}
                                        {badge && (
                                            <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${badge.className}`}>
                                                {badge.label}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                {(canPredict || status) && (
                                    <Link
                                        href={route('predictions.show', round.slug)}
                                        className="ml-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
                                    >
                                        {canPredict ? 'Predecir' : 'Ver'}
                                    </Link>
                                )}
                            </div>
                        );
                    })}

                    <div className="bg-white shadow rounded-lg p-5 flex items-center justify-between">
                        <div>
                            <h3 className="font-semibold text-gray-900">Predicciones Especiales</h3>
                            <p className="text-sm text-gray-500">Campeón · Sub-campeón · Goleador</p>
                        </div>
                        <Link
                            href={route('predictions.special')}
                            className="ml-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
                        >
                            Completar
                        </Link>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
