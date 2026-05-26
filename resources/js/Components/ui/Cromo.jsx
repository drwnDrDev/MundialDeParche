export default function Cromo({ corner = null, className = '', children }) {
    return (
        <div
            className={[
                'border-[3px] border-ink shadow-pop-xl rounded-[3px]',
                'relative overflow-hidden',
                className,
            ].join(' ')}
        >
            {corner && (
                <span
                    data-testid="cromo-corner"
                    className={[
                        'absolute top-2 right-0',
                        'bg-pop-red text-white',
                        'font-display text-[10px] uppercase',
                        'px-2 py-0.5 border-l-2 border-b-2 border-ink',
                    ].join(' ')}
                >
                    {corner}
                </span>
            )}
            {children}
        </div>
    );
}
