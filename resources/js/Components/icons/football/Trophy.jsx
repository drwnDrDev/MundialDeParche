export default function Trophy({ size = 40, color = 'var(--c-yel)', stroke = 'var(--c-ink)', sw = 2.5 }) {
    return (
        <svg width={size} height={size * 1.2} viewBox="0 0 60 72" fill="none">
            <path d="M14 16 C 4 16, 4 36, 16 36" stroke={stroke} strokeWidth={sw} fill="none" strokeLinecap="round" />
            <path d="M46 16 C 56 16, 56 36, 44 36" stroke={stroke} strokeWidth={sw} fill="none" strokeLinecap="round" />
            <path d="M14 8 H46 V28 C 46 40, 38 48, 30 48 C 22 48, 14 40, 14 28 Z" fill={color} stroke={stroke} strokeWidth={sw} strokeLinejoin="round" />
            <path d="M30 18 L32 24 L38 24 L33 28 L35 34 L30 30 L25 34 L27 28 L22 24 L28 24 Z" fill={stroke} />
            <rect x="26" y="48" width="8" height="8" fill={color} stroke={stroke} strokeWidth={sw} />
            <rect x="18" y="56" width="24" height="6" fill={color} stroke={stroke} strokeWidth={sw} />
            <rect x="14" y="62" width="32" height="6" fill={stroke} />
        </svg>
    );
}
