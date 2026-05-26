export default function PitchSwoosh({ width = 200, height = 80 }) {
    return (
        <svg width={width} height={height} viewBox="0 0 200 80" style={{ display: 'block' }}>
            <defs>
                <pattern id="pitch-stripes" x="0" y="0" width="20" height="80" patternUnits="userSpaceOnUse">
                    <rect width="10" height="80" fill="#1f7a3a" />
                    <rect x="10" width="10" height="80" fill="#226b34" />
                </pattern>
            </defs>
            <path d="M0 80 L0 50 C 60 10, 140 10, 200 50 L 200 80 Z" fill="url(#pitch-stripes)" stroke="var(--c-ink)" strokeWidth="2.5" />
            <line x1="60" y1="32" x2="140" y2="32" stroke="#fff" strokeWidth="1.5" strokeDasharray="3 3" />
        </svg>
    );
}
