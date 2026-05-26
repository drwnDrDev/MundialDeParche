const VARIANTS = {
    white: 'bg-white text-ink',
    red:   'bg-pop-red text-white',
    yel:   'bg-pop-yel text-ink',
    teal:  'bg-pop-teal text-ink',
    navy:  'bg-navy text-cream',
};

export default function Chip({ variant = 'white', className = '', children }) {
    return (
        <span
            className={[
                'inline-flex items-center gap-1',
                'rounded-full border-2 border-ink',
                'font-mono text-xs uppercase tracking-[.04em]',
                'px-2.5 py-0.5',
                VARIANTS[variant] ?? VARIANTS.white,
                className,
            ].join(' ')}
        >
            {children}
        </span>
    );
}
