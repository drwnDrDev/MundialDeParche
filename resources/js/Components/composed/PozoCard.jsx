import Cromo from '@/Components/ui/Cromo';
import { Trophy } from '@/Components/icons/football';

function PrizeSlot({ place, pct, amount, color }) {
    return (
        <div className="bg-black/35 border-2 border-ink p-[6px_8px]">
            <div className="flex items-center gap-1.5">
                <span className="font-display text-[18px]" style={{ color }}>{place}</span>
                <span className="font-mono text-[9px] opacity-70 tracking-[.08em]">{pct}</span>
            </div>
            <div className="font-mono font-bold text-[14px] mt-0.5" style={{ color }}>{amount}</div>
        </div>
    );
}

export default function PozoCard({ total, players, amountPerPlayer, prize1, prize2 }) {
    return (
        <Cromo className="bg-navy text-cream p-[10px_12px]">
            <div className="halftone halftone-yel absolute inset-0 opacity-35" />
            <div className="absolute right-[-6px] bottom-[-10px] -rotate-[8deg] opacity-95">
                <Trophy size={62} color="var(--c-yel)" />
            </div>
            <div className="relative">
                <div className="font-mono text-[9px] text-pop-yel tracking-[.12em]">POZO TOTAL</div>
                <div className="font-display text-[30px] leading-none mt-0.5 text-pop-yel">{total}</div>
                <div className="font-mono text-[10px] opacity-75 mt-0.5">
                    {players} jugadores · {amountPerPlayer} c/u
                </div>
            </div>
            <div className="grid grid-cols-2 gap-1.5 mt-2.5 relative">
                <PrizeSlot place="1°" pct="70%" amount={prize1} color="var(--c-yel)" />
                <PrizeSlot place="2°" pct="30%" amount={prize2} color="var(--c-cream)" />
            </div>
        </Cromo>
    );
}
