import { Burst } from '@/Components/ui';

export default function ChatBubble({ name, color, text, time, isMe = false, pinned = false, sticker }) {
    return (
        <div className={`flex gap-2 items-end ${isMe ? 'flex-row-reverse' : 'flex-row'}`}>
            {!isMe && (
                <div
                    className="w-8 h-8 rounded-full border-2 border-ink font-display text-[12px] text-white flex items-center justify-center flex-shrink-0"
                    style={{ background: color }}
                >
                    {name[0]}
                </div>
            )}
            <div className={`max-w-[78%] flex flex-col ${isMe ? 'items-end' : 'items-start'}`}>
                {!isMe && (
                    <div className="flex items-center gap-1.5 mb-0.5">
                        <span className="font-display text-[10px]" style={{ color }}>{name}</span>
                        <span className="font-mono text-[9px] opacity-55">{time}</span>
                    </div>
                )}
                <div className={[
                    'border-2.5 border-ink p-[8px_12px] rounded-[4px] text-[14px] leading-snug relative',
                    isMe
                        ? 'bg-pop-yel shadow-[-3px_3px_0_var(--c-ink)]'
                        : 'bg-white shadow-[3px_3px_0_var(--c-ink)]',
                ].join(' ')}>
                    {pinned && (
                        <div className="absolute -top-2 -right-2 bg-pop-red text-white border-2 border-ink px-1.5 font-display text-[9px] rotate-[6deg]">
                            FIJO
                        </div>
                    )}
                    {text}
                </div>
                {sticker && (
                    <div className="mt-1 -rotate-[4deg]">
                        <Burst color="red" size="sm" rotate={0}>
                            {sticker}
                        </Burst>
                    </div>
                )}
                {isMe && (
                    <span className="font-mono text-[9px] opacity-55 mt-0.5">{time} · enviado</span>
                )}
            </div>
        </div>
    );
}
