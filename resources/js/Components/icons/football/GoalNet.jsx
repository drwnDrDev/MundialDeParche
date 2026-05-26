export default function GoalNet({ size = 80, stroke = 'var(--c-ink)', sw = 2.5 }) {
    return (
        <svg width={size * 1.5} height={size} viewBox="0 0 120 80" fill="none">
            <path d="M8 12 L8 70 M112 12 L112 70 M8 12 L112 12" stroke={stroke} strokeWidth={sw + 1} strokeLinecap="square" />
            <g stroke={stroke} strokeWidth="1" opacity=".7">
                {Array.from({ length: 11 }).map((_, i) => (
                    <line key={`v-${i}`} x1={8 + i * 10.4} y1="12" x2={8 + i * 10.4} y2="70" />
                ))}
                {Array.from({ length: 7 }).map((_, i) => (
                    <line key={`h-${i}`} x1="8" y1={12 + i * 8.5} x2="112" y2={12 + i * 8.5} />
                ))}
            </g>
        </svg>
    );
}
