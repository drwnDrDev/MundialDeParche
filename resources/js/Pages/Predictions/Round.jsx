import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

// ---- helpers ----

function groupFixtures(fixtures) {
    return fixtures.reduce((acc, f) => {
        const key = f.group?.name ?? 'Sin Grupo';
        if (!acc[key]) acc[key] = [];
        acc[key].push(f);
        return acc;
    }, {});
}

function teamName(team, placeholder) {
    return team ? team.name : (placeholder ?? 'TBD');
}

// ---- sub-components ----

function ScoreInput({ value, onChange, disabled }) {
    return (
        <input
            type="number"
            min="0"
            max="20"
            value={value}
            onChange={e => onChange(parseInt(e.target.value, 10) || 0)}
            disabled={disabled}
            className="w-14 text-center rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm disabled:bg-gray-100"
        />
    );
}

function FixtureRow({ fixture, home, away, onChange, disabled }) {
    return (
        <div className="flex items-center gap-3 py-2 border-b last:border-0">
            <span className="flex-1 text-right text-sm font-medium text-gray-800 truncate">
                {teamName(fixture.home_team, fixture.home_placeholder)}
            </span>
            <ScoreInput value={home} onChange={v => onChange(fixture.id, 'home', v)} disabled={disabled} />
            <span className="text-gray-400 text-sm">-</span>
            <ScoreInput value={away} onChange={v => onChange(fixture.id, 'away', v)} disabled={disabled} />
            <span className="flex-1 text-left text-sm font-medium text-gray-800 truncate">
                {teamName(fixture.away_team, fixture.away_placeholder)}
            </span>
        </div>
    );
}

// ---- main page ----

export default function Round({ round, fixtures, predictions, submission }) {
    const isLocked   = submission?.status === 'locked';
    const isSubmitted = submission?.status === 'submitted';
    const isGroupStage = round.slug === 'grupos';

    // Initialize scores from existing predictions
    const initialScores = {};
    fixtures.forEach(f => {
        const pred = predictions[f.id];
        initialScores[f.id] = {
            home: pred ? pred.predicted_home : '',
            away: pred ? pred.predicted_away : '',
        };
    });

    const [scores, setScores] = useState(initialScores);

    function handleChange(fixtureId, side, value) {
        setScores(prev => ({
            ...prev,
            [fixtureId]: { ...prev[fixtureId], [side]: value },
        }));
    }

    function buildPayload() {
        const predictions = {};
        fixtures.forEach(f => {
            const s = scores[f.id];
            if (s.home !== '' && s.away !== '') {
                predictions[String(f.id)] = {
                    predicted_home: Number(s.home),
                    predicted_away: Number(s.away),
                };
            }
        });
        return { predictions };
    }

    // Validation for submit button
    const allFilled = fixtures.every(f => scores[f.id]?.home !== '' && scores[f.id]?.away !== '');

    const knockoutTieError = !isGroupStage && fixtures.some(f => {
        const s = scores[f.id];
        return s?.home !== '' && s?.away !== '' && Number(s.home) === Number(s.away);
    });

    const canSubmit = allFilled && !knockoutTieError && !isLocked && round.is_open;

    function handleSave() {
        router.post(route('predictions.save', round.id), buildPayload());
    }

    function handleSubmit() {
        router.post(route('predictions.submit', round.id), buildPayload());
    }

    const groups = isGroupStage ? groupFixtures(fixtures) : null;

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-800">{round.name}</h2>
                {submission && (
                    <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                        isLocked    ? 'bg-red-100 text-red-700' :
                        isSubmitted ? 'bg-green-100 text-green-700' :
                                      'bg-yellow-100 text-yellow-700'
                    }`}>
                        {isLocked ? 'Bloqueado' : isSubmitted ? 'Confirmado' : 'Borrador'}
                    </span>
                )}
            </div>
        }>
            <Head title={`Predecir — ${round.name}`} />
            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-6">
                    {isGroupStage ? (
                        Object.entries(groups).sort(([a], [b]) => a.localeCompare(b)).map(([groupName, gFixtures]) => (
                            <div key={groupName} className="bg-white shadow rounded-lg p-4">
                                <h3 className="text-sm font-bold text-gray-500 uppercase tracking-wide mb-3">
                                    Grupo {groupName}
                                </h3>
                                {gFixtures.map(f => (
                                    <FixtureRow
                                        key={f.id}
                                        fixture={f}
                                        home={scores[f.id]?.home ?? ''}
                                        away={scores[f.id]?.away ?? ''}
                                        onChange={handleChange}
                                        disabled={isLocked}
                                    />
                                ))}
                            </div>
                        ))
                    ) : (
                        <div className="bg-white shadow rounded-lg p-4">
                            {fixtures.map(f => (
                                <FixtureRow
                                    key={f.id}
                                    fixture={f}
                                    home={scores[f.id]?.home ?? ''}
                                    away={scores[f.id]?.away ?? ''}
                                    onChange={handleChange}
                                    disabled={isLocked}
                                />
                            ))}
                        </div>
                    )}

                    {knockoutTieError && (
                        <p className="text-sm text-red-600 font-medium">
                            En rondas de eliminación no puede haber empates — debe haber un ganador.
                        </p>
                    )}

                    {!isLocked && round.is_open && (
                        <div className="flex gap-3 justify-end">
                            <button
                                onClick={handleSave}
                                className="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300"
                            >
                                Guardar borrador
                            </button>
                            <button
                                onClick={handleSubmit}
                                disabled={!canSubmit}
                                className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Confirmar predicciones
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
