export default function Pennant({ color = 'var(--c-red)', text = 'GOL', stroke = 'var(--c-ink)', w = 60, h = 36, rotate = 0 }) {
    return (
        <div style={{ transform: rotate ? `rotate(${rotate}deg)` : undefined, display: 'inline-block' }}>
            <svg width={w} height={h} viewBox="0 0 60 36">
                <path
                    d="M0 2 L52 2 L60 18 L52 34 L0 34 Z"
                    fill={color} stroke={stroke} strokeWidth="2.5" strokeLinejoin="round"
                />
                <text x="22" y="22" textAnchor="middle" fontFamily="Bungee, sans-serif" fontSize="11" fill="#fff" letterSpacing=".05em">
                    {text}
                </text>
            </svg>
        </div>
    );
}
