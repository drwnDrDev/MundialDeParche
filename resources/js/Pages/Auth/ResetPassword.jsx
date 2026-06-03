import { Head, Link, useForm } from '@inertiajs/react';
import { Mark26, PitchSwoosh } from '@/Components/icons/football';

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

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Nueva contraseña" />
            <div className="min-h-screen bg-cream overflow-hidden">
            <div className="max-w-3xl mx-auto min-h-screen relative overflow-hidden">

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
                            NUEVA<br />
                            <span className="text-pop-teal" style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}>
                                CONTRASEÑA
                            </span>
                        </div>
                        <div className="font-mono text-[13px] mt-2 opacity-70 leading-snug">
                            Ingresa tu nueva contraseña para acceder a tu cuenta.
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="relative z-10 px-6 pt-6 flex flex-col gap-4">
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <Field
                            label="TU EMAIL"
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            readOnly
                            autoComplete="username"
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                        />

                        <Field
                            label="NUEVA CONTRASEÑA"
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            autoFocus
                            autoComplete="new-password"
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                        />

                        <Field
                            label="CONFIRMAR CONTRASEÑA"
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            autoComplete="new-password"
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            error={errors.password_confirmation}
                        />

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full py-4 bg-pop-teal text-ink font-display text-[18px] border-[2.5px] border-ink tracking-[.02em] disabled:opacity-60"
                            style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                        >
                            CAMBIAR CONTRASEÑA
                        </button>

                        <Link
                            href={route('login')}
                            className="block text-center font-mono text-[12px] underline opacity-60"
                        >
                            ← Volver al inicio de sesión
                        </Link>
                    </form>
                </div>
            </div>
            </div>
        </>
    );
}
