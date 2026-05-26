export default function Stadium({ size = 80, color = 'var(--c-teal)', stroke = 'var(--c-ink)', sw = 2.5 }) {
    return (
        <svg width={size * 1.6} height={size} viewBox="0 0 160 100" fill="none">
            <ellipse cx="80" cy="60" rx="74" ry="32" fill={color} stroke={stroke} strokeWidth={sw} />
            <ellipse cx="80" cy="60" rx="50" ry="18" fill="#1f7a3a" stroke={stroke} strokeWidth={sw} />
            <line x1="80" y1="42" x2="80" y2="78" stroke="#fff" strokeWidth="1.5" />
            <circle cx="80" cy="60" r="6" fill="none" stroke="#fff" strokeWidth="1.5" />
            <line x1="10" y1="48" x2="10" y2="18" stroke={stroke} strokeWidth={sw} />
            <rect x="4" y="10" width="12" height="10" fill="var(--c-yel)" stroke={stroke} strokeWidth={sw} />
            <line x1="150" y1="48" x2="150" y2="18" stroke={stroke} strokeWidth={sw} />
            <rect x="144" y="10" width="12" height="10" fill="var(--c-yel)" stroke={stroke} strokeWidth={sw} />
        </svg>
    );
}
