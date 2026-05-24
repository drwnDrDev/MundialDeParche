import { Link, usePage } from '@inertiajs/react';

export default function AdminLayout({ header, children }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="bg-gray-900 text-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <div className="flex items-center gap-6">
                            <Link href={route('admin.dashboard')} className="font-bold text-yellow-400">
                                PollaMundial Admin
                            </Link>
                            <Link href={route('admin.rounds.index')} className="text-sm hover:text-yellow-300">
                                Rondas
                            </Link>
                            <Link href={route('admin.teams.index')} className="text-sm hover:text-yellow-300">
                                Equipos
                            </Link>
                            <Link href={route('admin.fixtures.index')} className="text-sm hover:text-yellow-300">
                                Partidos
                            </Link>
                            <Link href={route('admin.players.index')} className="text-sm hover:text-yellow-300">
                                Jugadores
                            </Link>
                            <Link href={route('admin.users.index')} className="text-sm hover:text-yellow-300">
                                Usuarios
                            </Link>
                        </div>
                        <span className="text-sm text-gray-400">{auth.user.name}</span>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {children}
            </main>
        </div>
    );
}
