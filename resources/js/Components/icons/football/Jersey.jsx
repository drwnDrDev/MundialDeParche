export default function Jersey({ size = 40, color = 'var(--c-red)', stroke = 'var(--c-ink)', sw = 2.5, num = '10' }) {
    return (
        <svg width={size} height={size} viewBox="0 0 60 60" fill="none">
            <path
                d="M14 10 L22 6 L26 12 L34 12 L38 6 L46 10 L52 22 L44 26 L44 54 L16 54 L16 26 L8 22 Z"
                fill={color} stroke={stroke} strokeWidth={sw} strokeLinejoin="round"
            />
            <text x="30" y="42" textAnchor="middle" fontFamily="Bungee, sans-serif" fontSize="14" fill={stroke}>
                {num}
            </text>
        </svg>
    );
}
