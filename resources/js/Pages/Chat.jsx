import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import TabBar from '@/Components/composed/TabBar';
import ChatBubble from '@/Components/composed/ChatBubble';
import { SoccerBall } from '@/Components/icons/football';

const COLORS = ['yel', 'teal', 'red', 'cream'];

function mapMessage(msg, myId) {
    return {
        id:    msg.id,
        name:  msg.user.name.split(' ')[0].toUpperCase(),
        color: `var(--c-${COLORS[msg.user.id % 4]})`,
        text:  msg.content,
        time:  new Date(msg.created_at).toLocaleTimeString('es', {
            hour: '2-digit', minute: '2-digit', hour12: false,
        }),
        isMe: msg.user.id === myId,
    };
}

export default function Chat({ messages: initialMessages, liveMatch }) {
    const { auth } = usePage().props;
    const myId = auth.user.id;

    const [messages, setMessages]       = useState(initialMessages.map(m => mapMessage(m, myId)));
    const [onlineCount, setOnlineCount] = useState(0);
    const bottomRef = useRef(null);

    const { data, setData, post, processing, reset } = useForm({ content: '' });

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    useEffect(() => {
        const channel = window.Echo.join('quinela');

        channel
            .here(users => setOnlineCount(users.length))
            .joining(() => setOnlineCount(c => c + 1))
            .leaving(() => setOnlineCount(c => Math.max(0, c - 1)))
            .listen('.MessageSent', (event) => {
                const raw = {
                    id:         event.id,
                    user:       { id: event.user_id, name: event.user_name },
                    content:    event.content,
                    created_at: event.created_at,
                };
                setMessages(prev => [...prev, mapMessage(raw, myId)]);
            });

        return () => { window.Echo.leave('quinela'); };
    }, [myId]);

    const send = (e) => {
        e.preventDefault();
        if (!data.content.trim() || processing) return;
        post(route('chat.store'), { onSuccess: () => reset() });
    };

    return (
        <>
            <Head title="El Parche" />
            {/* 80px = approximate TabBar rendered height (pt-2.5 + pb-[22px] + icon ~36px) */}
            <div className="bg-cream overflow-hidden" style={{ height: '100vh' }}>
            <div className="max-w-3xl mx-auto h-full flex flex-col" style={{ paddingBottom: '79px' }}>

                {/* Header */}
                <div className="flex-shrink-0 px-4 pb-2.5 pt-1.5 border-b-[3px] border-ink bg-pop-yel relative overflow-hidden">
                    <div className="halftone absolute inset-0 pointer-events-none" style={{ opacity: .15 }} />
                    <div className="flex items-center gap-3 relative">
                        <div className="w-9 h-9 rounded-[6px] border-[2.5px] border-ink bg-pop-red flex items-center justify-center flex-shrink-0">
                            <SoccerBall size={22} />
                        </div>
                        <div className="flex-1">
                            <div className="font-display text-[18px] leading-none">EL PARCHE</div>
                            <div className="font-mono text-[10px] opacity-75 mt-0.5">
                                ● {onlineCount > 0 ? `${onlineCount} en línea` : 'conectando…'}
                            </div>
                        </div>
                        <div className="w-8 h-8 border-[2.5px] border-ink bg-white flex items-center justify-center font-display text-[16px]">
                            ⋯
                        </div>
                    </div>
                </div>

                {/* Live match banner */}
                {liveMatch && (
                    <div className="flex-shrink-0 px-4 py-2 bg-navy text-cream border-b-[3px] border-ink flex items-center gap-2.5 relative overflow-hidden">
                        <div className="absolute right-[-12px] top-[-8px] opacity-25 pointer-events-none">
                            <SoccerBall size={50} />
                        </div>
                        <span className="w-2 h-2 rounded-full bg-pop-red animate-[blink_1.2s_infinite]" />
                        <span className="font-pixel text-[16px] text-pop-yel">EN VIVO</span>
                        <span className="font-mono text-[13px] font-bold">
                            {liveMatch.teamA} {liveMatch.scoreA} - {liveMatch.scoreB} {liveMatch.teamB}
                        </span>
                        {liveMatch.minute && (
                            <span className="font-mono text-[11px] opacity-70 ml-auto z-10">
                                {liveMatch.minute}
                            </span>
                        )}
                    </div>
                )}

                {/* Messages */}
                <div className="flex-1 overflow-y-auto px-3.5 py-3.5 flex flex-col gap-3 min-h-0">
                    {messages.map(msg => (
                        <ChatBubble key={msg.id} {...msg} />
                    ))}
                    <div ref={bottomRef} />
                </div>

                {/* Input bar */}
                {auth.user.is_activated ? (
                    <div className="flex-shrink-0 flex items-center gap-2 px-3 py-2.5 bg-cream border-t-[3px] border-ink">
                        <button
                            type="button"
                            className="w-10 h-10 border-[2.5px] border-ink bg-pop-yel font-display text-[18px] shadow-pop-sm flex items-center justify-center flex-shrink-0"
                        >
                            +
                        </button>
                        <form onSubmit={send} className="flex-1 flex gap-2">
                            <input
                                value={data.content}
                                onChange={e => setData('content', e.target.value)}
                                placeholder="Escribe algo, parcero…"
                                className="flex-1 border-[2.5px] border-ink bg-white px-3.5 py-2.5 font-body text-[13px] shadow-pop-sm focus:outline-none"
                            />
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-11 h-11 border-[2.5px] border-ink bg-pop-red text-white font-display text-[18px] shadow-pop-sm flex items-center justify-center flex-shrink-0 disabled:opacity-60"
                            >
                                ▶
                            </button>
                        </form>
                    </div>
                ) : (
                    <div className="flex-shrink-0 flex items-center justify-between gap-3 px-4 py-3 bg-cream border-t-[3px] border-ink">
                        <div className="font-mono text-[10px] opacity-60 leading-tight">
                            Activa tu cuenta para<br />participar en el parche
                        </div>
                        <Link
                            href={route('activation')}
                            className="px-3 py-2 bg-ink text-cream font-display text-[11px] tracking-[.01em] border-[2px] border-ink flex-shrink-0"
                            style={{ boxShadow: '2px 2px 0 var(--c-yel)' }}
                        >
                            ACTIVAR →
                        </Link>
                    </div>
                )}
            </div>
            </div>
            <TabBar active="chat" />
        </>
    );
}
