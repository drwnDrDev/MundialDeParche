export default function Whistle({ size = 36, color = 'var(--c-yel)', stroke = 'var(--c-ink)', sw = 2.5 }) {
    return (
        <svg width={size * 1.4} height={size} viewBox="0 0 84 60" fill="none">
            <circle cx="34" cy="30" r="20" fill={color} stroke={stroke} strokeWidth={sw} />
            <rect x="44" y="22" width="34" height="16" fill={color} stroke={stroke} strokeWidth={sw} />
            <circle cx="34" cy="30" r="6" fill={stroke} />
            <path d="M80 16 L86 12 M82 30 L90 30 M80 44 L86 48" stroke={stroke} strokeWidth={sw} strokeLinecap="round" />
        </svg>
    );
}
