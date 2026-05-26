export default function SoccerBall({ size = 40, stroke = 'var(--c-ink)', sw = 2.5 }) {
    return (
        <svg width={size} height={size} viewBox="0 0 60 60" fill="none">
            <circle cx="30" cy="30" r="26" fill="#fff" stroke={stroke} strokeWidth={sw} />
            <path d="M30 18 L40 24 L36 36 L24 36 L20 24 Z" fill={stroke} />
            <path d="M30 18 L30 8" stroke={stroke} strokeWidth={sw} />
            <path d="M40 24 L50 22" stroke={stroke} strokeWidth={sw} />
            <path d="M36 36 L42 46" stroke={stroke} strokeWidth={sw} />
            <path d="M24 36 L18 46" stroke={stroke} strokeWidth={sw} />
            <path d="M20 24 L10 22" stroke={stroke} strokeWidth={sw} />
        </svg>
    );
}
