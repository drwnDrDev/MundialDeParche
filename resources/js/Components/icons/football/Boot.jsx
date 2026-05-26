export default function Boot({ size = 40, color = 'var(--c-ink)', stroke = 'var(--c-ink)', sw = 2.5 }) {
    return (
        <svg width={size * 1.4} height={size} viewBox="0 0 84 60" fill="none">
            <path
                d="M6 32 C 6 22, 14 18, 24 18 L 56 18 C 66 18, 76 24, 78 36 L 78 44 L 12 44 C 8 44, 6 40, 6 36 Z"
                fill={color} stroke={stroke} strokeWidth={sw} strokeLinejoin="round"
            />
            <path d="M28 22 L36 30 M36 22 L44 30 M44 22 L52 30" stroke="var(--c-yel)" strokeWidth="2" />
            <circle cx="18" cy="48" r="2.5" fill={stroke} />
            <circle cx="34" cy="48" r="2.5" fill={stroke} />
            <circle cx="50" cy="48" r="2.5" fill={stroke} />
            <circle cx="66" cy="48" r="2.5" fill={stroke} />
        </svg>
    );
}
