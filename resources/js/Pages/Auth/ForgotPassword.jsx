import { Head, useForm } from '@inertiajs/react';
import { Mark26, PitchSwoosh } from '@/Components/icons/football';
import { Link } from '@inertiajs/react';

function Field({ label, id, error, ...inputProps }) {
    return (
        <div>
            <div className="font-mono text-[11px] font-bold tracking-[.1em] mb-1.5 text-ink">
                {label}
            </div>
            <input
                id={id}
                className="w-full border-[2.5px] border-ink bg-white px-[14px] py-[12px] font-mono font-bold text-[14px] focus:outline-none focus:border-pop-teal"
                style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                {...inputProps}
            />
            {error && (
                <div className="font-mono text-[11px] text-pop-red mt-1">{error}</div>
            )}
        </div>
    );
}

function SuccessView({ adminWhatsapp }) {
    const msg = encodeURIComponent('Hola! Necesito que me envíes el link para restablecer mi contraseña de PollaMundial.');
    return (
        <div className="flex flex-col gap-5">
            <div className="border-[2.5px] border-ink bg-pop-teal/20 p-4"
                 style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}>
                <div className="font-display text-[16px] leading-tight">SOLICITUD ENVIADA</div>
                <div className="font-mono text-[11px] opacity-70 mt-1.5 leading-snug">
                    El admin revisará tu solicitud y te mandará el link de recuperación personalmente.
                </div>
            </div>

            <div>
                <div className="font-mono text-[11px] opacity-60 mb-2 tracking-[.06em]">
                    CONTACTA AL ADMIN POR WHATSAPP
                </div>
                <a
                    href={`https://wa.me/${adminWhatsapp}?text=${msg}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center justify-between w-full py-4 px-4 bg-ink text-cream font-display text-[16px] border-[2.5px] border-ink"
                    style={{ boxShadow: '4px 4px 0 var(--c-teal)' }}
                >
                    <span>ESCRIBIR POR WHATSAPP</span>
                    <span className="text-[22px]">📲</span>
                </a>
            </div>

            <Link
                href={route('login')}
                className="block text-center font-mono text-[12px] underline opacity-60"
            >
                ← Volver al inicio de sesión
            </Link>
        </div>
    );
}

export default function ForgotPassword({ status, adminWhatsapp }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    const sent = status === 'solicitud_enviada';

    return (
        <>
            <Head title="Recuperar contraseña" />
            <div className="min-h-screen bg-cream relative overflow-hidden">

                {/* Halftone corners */}
                <div className="halftone halftone-teal absolute top-0 left-0 w-[220px] h-[220px] pointer-events-none" style={{ opacity: .3 }} />
                <div className="halftone halftone-yel absolute bottom-0 right-0 w-[260px] h-[260px] pointer-events-none" style={{ opacity: .3 }} />

                {/* Pitch swoosh */}
                <div className="absolute bottom-0 left-0 right-0 opacity-85 pointer-events-none">
                    <PitchSwoosh width={390} height={120} />
                </div>

                {/* Header */}
                <div className="relative px-6 pt-3">
                    <div className="flex items-center justify-between">
                        <Link
                            href={route('login')}
                            className="w-8 h-8 border-[2px] border-ink rounded-[6px] flex items-center justify-center font-display text-[14px]"
                        >
                            ←
                        </Link>
                        <div style={{ transform: 'rotate(8deg)' }}>
                            <Mark26 size={32} fill="var(--c-teal)" accent="var(--c-yel)" />
                        </div>
                    </div>

                    <div className="mt-5">
                        <div className="font-display text-[36px] leading-none">
                            ¿SE TE<br />
                            <span className="text-pop-teal" style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}>
                                OLVIDÓ?
                            </span>
                        </div>
                        <div className="font-mono text-[13px] mt-2 opacity-70 leading-snug">
                            {sent
                                ? 'Solicitud enviada. El admin te manda el link.'
                                : 'Ingresa tu email y el admin te manda el link de recuperación.'
                            }
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="relative z-10 px-6 pt-6 flex flex-col gap-4">
                    {sent ? (
                        <SuccessView adminWhatsapp={adminWhatsapp} />
                    ) : (
                        <form onSubmit={submit} className="flex flex-col gap-4">
                            <Field
                                label="TU EMAIL"
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                autoComplete="email"
                                autoFocus
                                onChange={e => setData('email', e.target.value)}
                                error={errors.email}
                            />

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-4 bg-pop-teal text-ink font-display text-[18px] border-[2.5px] border-ink tracking-[.02em] disabled:opacity-60"
                                style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                            >
                                PEDIR LINK
                            </button>

                            <Link
                                href={route('login')}
                                className="block text-center font-mono text-[12px] underline opacity-60"
                            >
                                ← Volver al inicio de sesión
                            </Link>
                        </form>
                    )}
                </div>
            </div>
        </>
    );
}
