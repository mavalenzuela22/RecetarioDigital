import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

export default function Login() {
    const form = useForm({ email: '', password: '' });

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
            <p className="intro">Entra para consultar y operar la información de tu negocio.</p>
            <form onSubmit={submit} noValidate>
                <div className="field"><label htmlFor="email">Correo</label><input id="email" name="email" type="email" autoComplete="username" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} aria-invalid={!!form.errors.email} required />{form.errors.email && <p className="error-text" role="alert">{form.errors.email}</p>}</div>
                <div className="field"><label htmlFor="password">Contraseña</label><input id="password" name="password" type="password" autoComplete="current-password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} aria-invalid={!!form.errors.password} required />{form.errors.password && <p className="error-text" role="alert">{form.errors.password}</p>}</div>
                <button className="button primary mt-6 w-full" type="submit" disabled={form.processing}>{form.processing ? 'Entrando…' : 'Entrar'}</button>
            </form>
        </main>
    </>;
}
