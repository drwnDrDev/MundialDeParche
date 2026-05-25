import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function Chat({ messages: initialMessages }) {
    const [messages, setMessages] = useState(initialMessages);
    const bottomRef = useRef(null);
    const { data, setData, post, processing, reset } = useForm({ content: '' });

    useEffect(() => {
        const channel = window.Echo.join('quinela');

        channel.listen('.MessageSent', (event) => {
            setMessages((prev) => [
                ...prev,
                {
                    id:         event.id,
                    content:    event.content,
                    created_at: event.created_at,
                    user: {
                        id:     event.user_id,
                        name:   event.user_name,
                        avatar: event.user_avatar,
                    },
                },
            ]);
        });

        return () => {
            window.Echo.leave('quinela');
        };
    }, []);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    function handleSubmit(e) {
        e.preventDefault();
        if (!data.content.trim()) return;
        post(route('chat.store'), {
            preserveScroll: true,
            onSuccess: () => reset('content'),
        });
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Chat</h2>}>
            <Head title="Chat" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 flex flex-col gap-4">

                    <div className="bg-white shadow rounded-lg p-4 h-96 overflow-y-auto flex flex-col gap-3">
                        {messages.length === 0 && (
                            <p className="text-center text-sm text-gray-400 mt-auto mb-auto">
                                Aún no hay mensajes. ¡Sé el primero!
                            </p>
                        )}
                        {messages.map((msg) => (
                            <div key={msg.id} className="flex items-start gap-2">
                                <div className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-200 flex items-center justify-center text-xs font-bold text-indigo-700">
                                    {msg.user.name.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <span className="text-xs font-semibold text-gray-700">{msg.user.name}</span>
                                    <p className="text-sm text-gray-800">{msg.content}</p>
                                    <span className="text-xs text-gray-400">
                                        {new Date(msg.created_at).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' })}
                                    </span>
                                </div>
                            </div>
                        ))}
                        <div ref={bottomRef} />
                    </div>

                    <form onSubmit={handleSubmit} className="flex gap-2">
                        <input
                            type="text"
                            value={data.content}
                            onChange={(e) => setData('content', e.target.value)}
                            placeholder="Escribí un mensaje..."
                            maxLength={500}
                            className="flex-1 rounded-md border-gray-300 shadow-sm text-sm"
                        />
                        <button
                            type="submit"
                            disabled={processing || !data.content.trim()}
                            className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Enviar
                        </button>
                    </form>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
