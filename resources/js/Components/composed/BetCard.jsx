import Cromo from '@/Components/ui/Cromo';

export default function BetCard({ teamA, teamB, flagUrlA, flagUrlB, pick, pts, time, hot = false }) {
    return (
        <div style={{ minWidth: 158, transform: `rotate(${hot ? -2 : 1}deg)`, flexShrink: 0 }}>
            <Cromo corner={hot ? '¡EN VIVO!' : undefined} className="p-2.5">
                <div className="flex justify-between items-center gap-2">
                    <img src={flagUrlA} alt={teamA} className="h-4 w-6 object-cover border border-ink" />
                    <span className="font-display text-[16px] text-pop-red">{pick}</span>
                    <img src={flagUrlB} alt={teamB} className="h-4 w-6 object-cover border border-ink" />
                </div>
                <div className="flex justify-between mt-2 font-mono text-[10px] font-bold tracking-[.06em]">
                    <span>{teamA} vs {teamB}</span>
                    <span>{time}</span>
                </div>
                <div className="mt-1.5 pt-1.5 border-t border-dashed border-ink flex justify-between items-center font-mono text-[10px]">
                    <span className="opacity-70 tracking-[.06em]">POSIBLES</span>
                    <b className="font-display text-[12px] text-pop-red">{pts} PTS</b>
                </div>
            </Cromo>
        </div>
    );
}
