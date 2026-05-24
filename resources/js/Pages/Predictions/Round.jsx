import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Round({ round, fixtures, predictions, submission }) {
    return (
        <AuthenticatedLayout>
            <Head title={round?.name ?? 'Predicciones'} />
        </AuthenticatedLayout>
    );
}
