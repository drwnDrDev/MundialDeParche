import { Trophy, SoccerBall, Boot } from '@/Components/icons/football';

export default function StatCard({ label, value, sub, color = 'red', icon }) {
    const valueColor = color === 'yel' ? 'text-ink' : `text-pop-${color}`;

    return (
        <div className="border-2.5 border-ink bg-white p-2.5 text-center shadow-pop relative overflow-hidden">
            <div
                className={`halftone halftone-${color} absolute top-0 right-0 w-[30px] h-[30px]`}
                style={{
                    WebkitMaskImage: 'radial-gradient(circle at 100% 0%, #000, transparent 70%)',
                    maskImage: 'radial-gradient(circle at 100% 0%, #000, transparent 70%)',
                }}
            />
            <div className="absolute top-1 left-1 opacity-90">
                {icon === 'trophy' && <Trophy size={16} color={`var(--c-${color})`} />}
                {icon === 'ball'   && <SoccerBall size={16} />}
                {icon === 'boot'   && <Boot size={14} color={`var(--c-${color})`} />}
            </div>
            <div className="font-mono text-[9px] tracking-[.1em] opacity-80 mt-3.5">{label}</div>
            <div className={`font-display text-[22px] mt-0.5 ${valueColor}`}>{value}</div>
            <div className="font-mono text-[10px] opacity-60">{sub}</div>
        </div>
    );
}
