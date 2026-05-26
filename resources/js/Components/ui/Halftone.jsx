const COLOR_MAP = {
    ink:  'halftone',
    red:  'halftone-red',
    yel:  'halftone-yel',
    teal: 'halftone-teal',
    navy: 'halftone-navy',
};

export default function Halftone({ color = 'ink', className = '', children }) {
    return (
        <div className={['relative', className].join(' ')}>
            <div
                data-halftone-overlay
                className={[
                    'absolute inset-0 pointer-events-none',
                    COLOR_MAP[color] ?? COLOR_MAP.ink,
                ].join(' ')}
            />
            {children}
        </div>
    );
}
