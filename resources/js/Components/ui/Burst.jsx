const STAR_CLIP = 'polygon(50% 0%, 54% 8%, 60% 4%, 61% 12%, 68% 9%, 67% 17%, 75% 16%, 72% 24%, 80% 25%, 75% 32%, 83% 35%, 76% 41%, 84% 46%, 76% 50%, 83% 55%, 75% 58%, 80% 65%, 72% 66%, 75% 74%, 67% 73%, 68% 81%, 61% 78%, 60% 86%, 54% 82%, 50% 90%, 46% 82%, 40% 86%, 39% 78%, 32% 81%, 33% 73%, 25% 74%, 28% 66%, 20% 65%, 25% 58%, 17% 55%, 24% 50%, 17% 46%, 25% 41%, 17% 35%, 24% 32%, 20% 25%, 28% 24%, 25% 16%, 33% 17%, 32% 9%, 39% 12%, 40% 4%, 46% 8%)';

const COLORS = {
    yel:  { bg: 'bg-pop-yel', text: 'text-ink' },
    red:  { bg: 'bg-pop-red', text: 'text-white' },
    teal: { bg: 'bg-pop-teal', text: 'text-ink' },
};

const SIZES = {
    sm: { outer: 'w-12 h-12',  text: 'text-[10px]' },
    md: { outer: 'w-20 h-20',  text: 'text-xs' },
    lg: { outer: 'w-28 h-28',  text: 'text-sm' },
    xl: { outer: 'w-36 h-36',  text: 'text-base' },
};

export default function Burst({
    color = 'yel',
    size = 'md',
    rotate = 0,
    className = '',
    children,
}) {
    const c = COLORS[color] ?? COLORS.yel;
    const s = SIZES[size] ?? SIZES.md;

    return (
        <div
            className={['relative flex items-center justify-center', s.outer, className].join(' ')}
            style={{ transform: rotate ? `rotate(${rotate}deg)` : undefined }}
        >
            {/* Capa ink (exterior) */}
            <div
                className="absolute inset-0 bg-ink"
                style={{ clipPath: STAR_CLIP }}
            />
            {/* Capa color (interior, ~94% escala) */}
            <div
                data-burst-inner
                className={['absolute', c.bg].join(' ')}
                style={{
                    clipPath: STAR_CLIP,
                    inset: '3%',
                }}
            />
            {/* Texto */}
            <span
                className={[
                    'relative z-10 font-display uppercase text-center leading-tight',
                    c.text,
                    s.text,
                ].join(' ')}
            >
                {children}
            </span>
        </div>
    );
}
