import FlagSmall from './FlagSmall';

export default function HostStrip({ height = 22 }) {
    return (
        <div className="inline-flex items-center gap-1 px-2 py-0.5 bg-white border-2 border-ink shadow-pop-sm">
            <span className="font-mono font-bold text-[9px] tracking-[.1em]">USA</span>
            <FlagSmall code="us" h={height - 8} />
            <span className="font-mono font-bold text-[9px] tracking-[.1em]">·</span>
            <FlagSmall code="ca" h={height - 8} />
            <span className="font-mono font-bold text-[9px] tracking-[.1em]">CAN</span>
            <span className="font-mono font-bold text-[9px] tracking-[.1em]">·</span>
            <FlagSmall code="mx" h={height - 8} />
            <span className="font-mono font-bold text-[9px] tracking-[.1em]">MEX</span>
        </div>
    );
}
