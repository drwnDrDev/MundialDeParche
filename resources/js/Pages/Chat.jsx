import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Chat({ messages }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Chat
                </h2>
            }
        >
            <Head title="Chat" />
        </AuthenticatedLayout>
    );
}
