import { Head, Link, useForm } from '@inertiajs/react';
import { SoccerBall, Mark26, PitchSwoosh } from '@/Components/icons/football';

function Field({ label, id, error, ...inputProps }) {
    return (
        <div>
            <div className="font-mono text-[11px] font-bold tracking-[.1em] mb-1.5 text-ink">
                {label}
            </div>
            <input
                id={id}
                className="w-full border-[2.5px] border-ink bg-white px-[14px] py-[12px] font-mono font-bold text-[14px] focus:outline-none focus:border-pop-red"
                style={{ boxShadow: '3px 3px 0 var(--c-ink)' }}
                {...inputProps}
            />
            {error && (
                <div className="font-mono text-[11px] text-pop-red mt-1">{error}</div>
            )}
        </div>
    );
}

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Entra al Parche — Registro" />
            <div className="min-h-screen bg-cream overflow-hidden">
            <div className="max-w-3xl mx-auto min-h-screen relative overflow-hidden">

                {/* Halftone corners */}
                <div className="halftone halftone-red absolute top-0 left-0 w-[220px] h-[220px] pointer-events-none" style={{ opacity: .35 }} />
                <div className="halftone halftone-teal absolute bottom-0 right-0 w-[260px] h-[260px] pointer-events-none" style={{ opacity: .35 }} />

                {/* Pitch swoosh al fondo */}
                <div className="absolute bottom-0 left-0 right-0 opacity-85 pointer-events-none">
                    <PitchSwoosh width={390} height={120} />
                </div>

                {/* Header */}
                <div className="relative px-6 pt-3">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2.5">
                            <Link
                                href="/"
                                className="w-8 h-8 border-[2px] border-ink rounded-[6px] flex items-center justify-center font-display text-[14px]"
                            >
                                ←
                            </Link>
                            <div className="font-pixel text-[18px]">PASO 1 / 2</div>
                        </div>
                        <div style={{ transform: 'rotate(8deg)' }}>
                            <Mark26 size={32} fill="var(--c-red)" accent="var(--c-yel)" />
                        </div>
                    </div>

                    <div className="mt-5">
                        <div className="font-display text-[36px] leading-none">
                            Entra al<br />
                            <span className="text-pop-red" style={{ WebkitTextStroke: '1.5px var(--c-ink)' }}>
                                Parche!
                            </span>
                        </div>
                        <div className="font-body text-[14px] mt-2 opacity-80">
                            Crea tu cuenta y mídete con los duros el Mundial '26.
                        </div>
                    </div>
                </div>

                {/* Form */}
                <form onSubmit={submit} className="relative z-10 px-6 pt-6 flex flex-col gap-3.5">
                    <Field
                        label="NICKNAME"
                        id="name"
                        type="text"
                        name="name"
                        value={data.name}
                        placeholder="ej: lucho_14, GolazoKing"
                        autoComplete="nickname"
                        autoFocus
                        onChange={e => setData('name', e.target.value)}
                        error={errors.name}
                    />

                    <Field
                        label="EMAIL"
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        autoComplete="username"
                        onChange={e => setData('email', e.target.value)}
                        error={errors.email}
                    />

                    <Field
                        label="CONTRASEÑA"
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        autoComplete="new-password"
                        onChange={e => setData('password', e.target.value)}
                        error={errors.password}
                    />

                    <Field
                        label="CONFIRMAR CONTRASEÑA"
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        autoComplete="new-password"
                        onChange={e => setData('password_confirmation', e.target.value)}
                        error={errors.password_confirmation}
                    />

                    <button
                        type="submit"
                        disabled={processing}
                        className="mt-2 w-full py-4 bg-pop-red text-white font-display text-[18px] border-[2.5px] border-ink tracking-[.02em] disabled:opacity-60"
                        style={{ boxShadow: '4px 4px 0 var(--c-ink)' }}
                    >
                        DALE, REGISTRARME →
                    </button>
                </form>

                {/* Burst badge */}
                <div
                    className="absolute top-[80px] right-[18px] z-10 bg-pop-yel border-[2.5px] border-ink px-3 py-2 font-display text-[12px] text-ink text-center"
                    style={{ transform: 'rotate(12deg)', boxShadow: '3px 3px 0 var(--c-ink)' }}
                >
                    Mundial<br />2026
                </div>

                {/* Ghost ball */}
                <div className="absolute top-[270px] right-[-8px] opacity-15">
                    <SoccerBall size={120} />
                </div>

                {/* Footer */}
                <div className="absolute bottom-[18px] left-0 right-0 text-center font-mono text-[12px] z-10">
                    ¿Ya tienes cuenta?{' '}
                    <Link href={route('login')} className="font-bold underline">
                        Entra aquí
                    </Link>
                </div>
            </div>
            </div>
        </>
    );
}
