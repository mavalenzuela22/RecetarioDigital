import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';

type LoginPageProps = {
    errors?: {
        email?: string;
    };
};

export default function Login() {
    const form = useForm({ email: '', password: '' });
    const { props } = usePage<LoginPageProps>();
    const emailErrors = [...new Set([props.errors?.email, form.errors.email].filter((error): error is string => Boolean(error)))];

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/login', { onError: () => form.reset('password') });
    }

    return <>
        <Head title="Iniciar sesión" />
        <main className="flow min-h-screen">
            <header className="flow-header"><span className="brand">EmprendimientoOS</span></header>
            <p className="eyebrow">EmprendimientoOS</p>
            <h1>Iniciar sesión</h1>
            <p className="intro">Entra con la cuenta de Google que tiene acceso a tu negocio.</p>
            {emailErrors.map((error) => <p className="error-message" role="alert" key={error}>{error}</p>)}
            <a className="button primary w-full" href="/auth/google/redirect">Continuar con Google</a>
            <section className="mt-8 rounded-2xl bg-paper p-4" aria-labelledby="support-login-title">
                <h2 id="support-login-title">Acceso de soporte</h2>
                <p className="help">Usa tu contraseña de recuperación solo si no puedes entrar con Google.</p>
                <form onSubmit={submit} noValidate>
                    <div className="field"><label htmlFor="email">Correo</label><input id="email" name="email" type="email" autoComplete="username" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} aria-invalid={emailErrors.length > 0} required /></div>
                    <div className="field"><label htmlFor="password">Contraseña de recuperación</label><input id="password" name="password" type="password" autoComplete="current-password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} aria-invalid={!!form.errors.password} required />{form.errors.password && <p className="error-text" role="alert">{form.errors.password}</p>}</div>
                    <button className="button secondary mt-6 w-full" type="submit" disabled={form.processing}>{form.processing ? 'Entrando…' : 'Entrar con contraseña'}</button>
                </form>
            </section>
            <p className="help mt-8">¿Recibiste una invitación? Abre el enlace que te compartió la administradora.</p>
        </main>
    </>;
}
