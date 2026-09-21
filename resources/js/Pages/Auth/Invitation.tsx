import { Head } from '@inertiajs/react';

export default function Invitation({ email, googleUrl, expiresAt }: { email: string; googleUrl: string; expiresAt: string | null }) {
    return <>
        <Head title="Tu invitación" />
        <main className="flow min-h-screen">
            <header className="flow-header"><span className="brand">EmprendimientoOS</span></header>
            <p className="eyebrow">Tu invitación</p>
            <h1>Entra a EmprendimientoOS</h1>
            <p className="intro">Esta invitación es para <strong>{email}</strong>. Continúa con esa misma cuenta de Google para entrar.</p>
            <a className="button primary w-full" href={googleUrl}>Continuar con Google</a>
            {expiresAt && <p className="help text-center">Disponible hasta el {expiresAt}.</p>}
            <p className="help mt-8">No necesitas crear una contraseña ni registrarte.</p>
        </main>
    </>;
}
