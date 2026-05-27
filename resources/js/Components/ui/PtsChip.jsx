/**
 * PtsChip — chip de puntos por tipo de acierto.
 * type: 'exact' | 'result' | 'classifier'
 * pts: número entero
 */
export default function PtsChip({ pts, type }) {
    if (!pts) {
        return (
            <span className="px-1.5 py-0.5 font-mono text-[10px] border border-ink/25 text-ink/35 leading-none">
                +0
            </span>
        );
    }

    const colors = {
        exact:      'bg-pop-red text-white border-ink',
        result:     'bg-pop-teal text-ink border-ink',
        classifier: 'bg-pop-yel text-ink border-ink',
    };

    return (
        <span
            className={`px-1.5 py-0.5 font-mono text-[10px] font-bold border leading-none ${colors[type] ?? 'bg-ink text-cream border-ink'}`}
            style={{ boxShadow: '1px 1px 0 var(--c-ink)' }}
        >
            +{pts}
        </span>
    );
}
