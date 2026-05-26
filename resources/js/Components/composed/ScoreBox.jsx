export default function ScoreBox({ value, filled = false }) {
    return (
        <div
            className={[
                'w-[30px] h-[34px] border-2.5 border-ink shadow-pop-sm',
                'flex items-center justify-center font-display text-[18px]',
                filled ? 'bg-pop-yel text-ink' : 'bg-white text-black/25',
            ].join(' ')}
        >
            {value !== null && value !== undefined ? value : '—'}
        </div>
    );
}
