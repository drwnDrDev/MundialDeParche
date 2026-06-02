import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

function GroupTable({ group }) {
    return (
        <div className="bg-white shadow rounded-lg overflow-hidden">
            <div className="bg-gray-800 text-white px-4 py-2 flex items-center justify-between">
                <span className="font-bold text-sm tracking-wide">GRUPO {group.name}</span>
            </div>
            <table className="w-full text-xs">
                <thead>
                    <tr className="bg-gray-100 text-gray-600 uppercase">
                        <th className="text-left px-3 py-2 w-6">#</th>
                        <th className="text-left px-3 py-2">Equipo</th>
                        <th className="text-center px-2 py-2 w-8">J</th>
                        <th className="text-center px-2 py-2 w-8">G</th>
                        <th className="text-center px-2 py-2 w-8">E</th>
                        <th className="text-center px-2 py-2 w-8">P</th>
                        <th className="text-center px-2 py-2 w-8">GF</th>
                        <th className="text-center px-2 py-2 w-8">GC</th>
                        <th className="text-center px-2 py-2 w-8">DG</th>
                        <th className="text-center px-2 py-2 w-8 font-bold text-gray-800">PTS</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                    {group.rows.map((row, i) => {
                        const isClassified = i < 2;
                        const isPotential  = i === 2;
                        return (
                            <tr
                                key={row.team_id}
                                className={
                                    isClassified ? 'bg-green-50' :
                                    isPotential  ? 'bg-yellow-50' :
                                    'bg-white'
                                }
                            >
                                <td className="px-3 py-2 text-gray-400 font-bold">{i + 1}</td>
                                <td className="px-3 py-2">
                                    <div className="flex items-center gap-2">
                                        {row.flag_url && (
                                            <img src={row.flag_url} alt="" className="h-4 w-6 object-cover border border-gray-200 flex-shrink-0" />
                                        )}
                                        <span className="font-medium text-gray-800 truncate">
                                            {row.fifa_code ?? row.team_name ?? '?'}
                                        </span>
                                        {isClassified && <span className="text-green-600 text-[10px] font-bold ml-1">✓</span>}
                                    </div>
                                </td>
                                <td className="px-2 py-2 text-center text-gray-600">{row.played}</td>
                                <td className="px-2 py-2 text-center text-gray-600">{row.w}</td>
                                <td className="px-2 py-2 text-center text-gray-600">{row.d}</td>
                                <td className="px-2 py-2 text-center text-gray-600">{row.l}</td>
                                <td className="px-2 py-2 text-center text-gray-600">{row.gf}</td>
                                <td className="px-2 py-2 text-center text-gray-600">{row.ga}</td>
                                <td className={`px-2 py-2 text-center font-medium ${row.gd > 0 ? 'text-green-600' : row.gd < 0 ? 'text-red-600' : 'text-gray-600'}`}>
                                    {row.gd > 0 ? `+${row.gd}` : row.gd}
                                </td>
                                <td className="px-2 py-2 text-center font-bold text-gray-800">{row.pts}</td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

export default function Standings({ groups, isLocked }) {
    return (
        <AdminLayout header="Posiciones — Fase de Grupos">
            <Head title="Posiciones" />
            <div className="py-8">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

                    {/* Legend */}
                    <div className="flex items-center gap-4 mb-6 text-xs text-gray-600">
                        <div className="flex items-center gap-1.5">
                            <div className="w-3 h-3 rounded-sm bg-green-100 border border-green-300" />
                            <span>Clasificado</span>
                        </div>
                        <div className="flex items-center gap-1.5">
                            <div className="w-3 h-3 rounded-sm bg-yellow-100 border border-yellow-300" />
                            <span>Posible mejor tercero</span>
                        </div>
                        {!isLocked && (
                            <span className="text-amber-600 font-medium">⚠ Ronda en curso — los resultados son parciales</span>
                        )}
                        {isLocked && (
                            <span className="text-green-700 font-medium">✓ Ronda finalizada</span>
                        )}
                    </div>

                    {groups.length === 0 ? (
                        <div className="text-center text-gray-500 py-12">
                            No hay fixtures de la fase de grupos aún.
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                            {groups.map(group => (
                                <GroupTable key={group.name} group={group} />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
