export default function PtsBadge({ value, rank }) {
    return (
        <div className="inline-flex items-center gap-1.5 py-1 pl-1.5 pr-2.5 bg-ink border-2 border-ink rounded-full shadow-pop-sm">
            <span className="w-5 h-5 rounded-full bg-pop-yel text-ink font-display text-[11px] flex items-center justify-center flex-shrink-0">
                P
            </span>
            <span className="font-display text-[13px] text-pop-yel">{value}</span>
            <span className="font-mono text-[10px] text-cream opacity-70">· {rank}</span>
        </div>
    );
}
