import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const NAV_LINKS = [
    { label: 'Dashboard',   href: () => route('admin.dashboard') },
    { label: 'Rondas',      href: () => route('admin.rounds.index') },
    { label: 'Score Entry', href: () => route('admin.score-entry') },
    { label: 'Partidos',    href: () => route('admin.fixtures.index') },
    { label: 'Equipos',     href: () => route('admin.teams.index') },
    { label: 'Jugadores',   href: () => route('admin.players.index') },
    { label: 'Usuarios',    href: () => route('admin.users.index') },
    { label: 'Chat',        href: () => route('chat.index') },
];

export default function AdminLayout({ header, children }) {
    const { auth, flash } = usePage().props;
    const [open, setOpen] = useState(false);

    const currentPath = window.location.pathname;
    const isActive = (href) => currentPath.startsWith(href().split('?')[0]);

    return (
        <div className="min-h-screen bg-gray-100">
            {/* Navbar */}
            <nav className="bg-gray-900 text-white">
                <div className="mx-auto max-w-7xl px-4">
                    <div className="flex h-14 items-center justify-between">
                        {/* Logo */}
                        <Link href={route('admin.dashboard')} className="font-bold text-yellow-400 text-sm flex-shrink-0">
                            ⚽ Admin
                        </Link>

                        {/* Desktop nav */}
                        <div className="hidden md:flex items-center gap-1 ml-6">
                            {NAV_LINKS.map(({ label, href }) => (
                                <Link key={label} href={href()}
                                    className={[
                                        'px-3 py-1.5 rounded text-sm transition-colors',
                                        isActive(href) ? 'bg-yellow-400 text-gray-900 font-medium' : 'text-gray-300 hover:text-white hover:bg-gray-700',
                                    ].join(' ')}>
                                    {label}
                                </Link>
                            ))}
                        </div>

                        <div className="flex items-center gap-3">
                            <span className="hidden md:block text-xs text-gray-400">{auth.user.name}</span>
                            {/* Hamburger */}
                            <button
                                onClick={() => setOpen(o => !o)}
                                className="md:hidden p-2 rounded text-gray-300 hover:text-white hover:bg-gray-700"
                                aria-label="Menú"
                            >
                                {open ? '✕' : '☰'}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Mobile menu */}
                {open && (
                    <div className="md:hidden border-t border-gray-700 px-4 py-3 flex flex-col gap-1">
                        {NAV_LINKS.map(({ label, href }) => (
                            <Link key={label} href={href()}
                                onClick={() => setOpen(false)}
                                className={[
                                    'block px-3 py-2 rounded text-sm',
                                    isActive(href) ? 'bg-yellow-400 text-gray-900 font-medium' : 'text-gray-300 hover:bg-gray-700',
                                ].join(' ')}>
                                {label}
                            </Link>
                        ))}
                        <div className="pt-2 border-t border-gray-700 text-xs text-gray-500">{auth.user.name}</div>
                    </div>
                )}
            </nav>

            {/* Flash banner */}
            {flash?.status && (
                <div className="bg-green-50 border-b border-green-200 px-4 py-2 text-sm text-green-800">
                    {flash.status}
                </div>
            )}

            {/* Page header */}
            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-4">
                        <h2 className="text-lg font-semibold text-gray-800">{header}</h2>
                    </div>
                </header>
            )}

            {/* Content */}
            <main className="mx-auto max-w-7xl px-4 py-6">
                {children}
            </main>
        </div>
    );
}
