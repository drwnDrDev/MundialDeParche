import { Trophy, SoccerBall } from '@/Components/icons/football';

const STEP = {
    1: { bg: 'bg-pop-yel', textColor: 'text-ink',  h: 108 },
    2: { bg: 'bg-cream',   textColor: 'text-ink',  h: 80 },
    3: { bg: 'bg-pop-red', textColor: 'text-white', h: 64 },
};

export default function PodiumStep({ place, pts, tied, crown = false }) {
    const isTie = tied.length > 1;
    const { bg, textColor, h } = STEP[place];

    return (
        <div className="flex-1 text-center flex flex-col items-center relative">
            {crown && (
                <div className="mb-0.5">
                    <Trophy size={26} color="var(--c-yel)" />
                </div>
            )}

            {/* Avatar stack */}
            <div className="relative mb-1 h-[50px] flex items-center justify-center">
                {tied.slice(0, 3).map((u, i) => (
                    <div
                        key={i}
                        className="w-11 h-11 rounded-full border-2.5 border-ink shadow-pop-sm font-display text-[16px] text-ink flex items-center justify-center flex-shrink-0"
                        style={{ background: u.color, marginLeft: i > 0 ? -16 : 0, zIndex: 3 - i }}
                    >
                        {u.name[0]}
                    </div>
                ))}
                {tied.length > 3 && (
                    <div
                        className="w-7 h-7 rounded-full bg-ink text-pop-yel border-2.5 border-ink font-mono text-[10px] font-bold flex items-center justify-center flex-shrink-0"
                        style={{ marginLeft: -10, zIndex: 0 }}
                    >
                        +{tied.length - 3}
                    </div>
                )}
            </div>

            {isTie && (
                <div className="inline-block font-mono text-[9px] font-bold tracking-[.08em] bg-pop-red text-white px-1.5 py-0.5 border-[1.5px] border-ink mb-0.5">
                    {tied.length} EMPATAN
                </div>
            )}

            <div className="font-display text-[10px] mt-0.5 min-h-[12px]">
                {isTie ? '···' : tied[0].name}
            </div>
            <div className="font-mono text-[10px] opacity-80 font-bold mt-0.5">{pts} pts</div>

            {/* Step block */}
            <div
                className={[
                    'mt-1 w-full border-2.5 border-ink shadow-pop',
                    'flex items-start justify-center pt-2',
                    'font-display text-[28px] relative overflow-hidden',
                    bg, textColor,
                ].join(' ')}
                style={{ height: h }}
            >
                {place}°
                <div className="halftone absolute inset-0 opacity-[.12]" />
                {place === 1 && (
                    <div className="absolute bottom-1.5 left-1/2 -translate-x-1/2 opacity-35">
                        <SoccerBall size={32} />
                    </div>
                )}
            </div>
        </div>
    );
}
