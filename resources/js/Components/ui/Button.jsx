const VARIANTS = {
    yel:   'bg-pop-yel text-ink',
    red:   'bg-pop-red text-white',
    teal:  'bg-pop-teal text-white',
    navy:  'bg-navy text-cream',
    ghost: 'bg-transparent border-transparent shadow-none',
};

const SIZES = {
    md: 'px-4 py-2.5 text-sm',
    lg: 'px-[26px] py-[18px] text-lg',
};

export default function Button({
    variant = 'yel',
    size = 'md',
    full = false,
    className = '',
    children,
    ...props
}) {
    return (
        <button
            className={[
                'font-display uppercase tracking-[.01em] leading-none',
                'border-2.5 border-ink shadow-pop-md rounded-none',
                'transition-transform',
                'active:translate-x-[3px] active:translate-y-[3px] active:shadow-pop-sm',
                'disabled:opacity-50 disabled:pointer-events-none',
                VARIANTS[variant] ?? VARIANTS.yel,
                SIZES[size] ?? SIZES.md,
                full ? 'w-full flex justify-center' : '',
                className,
            ].join(' ')}
            {...props}
        >
            {children}
        </button>
    );
}
