export default function RankRow({ position, name, pts, delta, isYou = false, tiedCount }) {
    const isPositive = delta.startsWith('+');
    const absVal = delta.replace(/[+-]/, '');

    return (
        <div className={[
            'flex items-center gap-2.5 px-2.5 py-2 border-2.5 border-ink shadow-pop relative',
            isYou ? 'bg-pop-yel' : 'bg-white',
        ].join(' ')}>
            <div className="w-9 text-center flex-shrink-0">
                <div className={`font-display text-[16px] leading-none ${isYou ? 'text-pop-red' : 'text-ink'}`}>
                    {position}°
                </div>
                {tiedCount && (
                    <div className="font-mono text-[7px] font-bold text-pop-red tracking-[.06em] mt-0.5">
                        ={tiedCount}
                    </div>
                )}
            </div>
            <div className="w-7 h-7 rounded-full bg-pop-teal text-white border-2 border-ink font-display text-[12px] flex items-center justify-center flex-shrink-0">
                {name[0]}
            </div>
            <div className="flex-1 min-w-0">
                <div className="font-display text-[13px] leading-none">{name}</div>
            </div>
            <div className="text-right flex-shrink-0">
                <div className="font-display text-[16px] leading-none">{pts}</div>
                <div className="font-mono text-[8px] opacity-70 tracking-[.05em]">PUNTOS</div>
            </div>
            <div className={[
                'font-mono font-bold text-[10px] px-1.5 py-0.5 border-[1.5px] border-ink min-w-[32px] text-center flex-shrink-0',
                isPositive ? 'bg-pop-teal text-white' : 'bg-pop-red text-white',
            ].join(' ')}>
                {isPositive ? '▲' : '▼'}{absVal}
            </div>
        </div>
    );
}
