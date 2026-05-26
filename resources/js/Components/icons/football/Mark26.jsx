export default function Mark26({ size = 60, fill = 'var(--c-red)', stroke = 'var(--c-ink)', sw = 2.5, accent = 'var(--c-yel)' }) {
    return (
        <svg width={size * 1.6} height={size} viewBox="0 0 96 60" fill="none">
            <path
                d="M6 18 C 6 6, 18 2, 28 6 C 38 10, 38 22, 30 28 L 12 46 L 38 46 L 38 54 L 4 54 L 4 46 L 24 26 C 28 22, 28 14, 22 14 C 16 14, 14 18, 14 22 L 6 22 Z"
                fill={fill} stroke={stroke} strokeWidth={sw} strokeLinejoin="round"
            />
            <path
                d="M84 6 C 76 6, 68 14, 64 24 C 60 34, 60 46, 70 52 C 80 58, 92 52, 92 40 C 92 30, 84 26, 76 28 C 72 30, 68 34, 70 40 C 72 44, 78 44, 80 40 C 82 36, 76 34, 76 38"
                fill={fill} stroke={stroke} strokeWidth={sw} strokeLinejoin="round"
            />
            <path
                d="M48 8 L50 14 L56 14 L51 18 L53 24 L48 20 L43 24 L45 18 L40 14 L46 14 Z"
                fill={accent} stroke={stroke} strokeWidth="1.5"
            />
        </svg>
    );
}
