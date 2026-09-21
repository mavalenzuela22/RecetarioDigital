import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';

type UserRow = { id: number; name: string; email: string; active: boolean; admin: boolean; googleLinked: boolean };
type InvitationRow = { id: number; email: string; status: string; expiresAt: string | null; createdAt: string | null };

export default function AccessIndex({ users, invitations, invitationLink, notice, currentUserId }: { users: UserRow[]; invitations: InvitationRow[]; invitationLink: string | null; notice: string | null; currentUserId: number }) {
    const invitationForm = useForm({ email: '' });
    const passwordForm = useForm({ password: '', password_confirmation: '' });
    const [copied, setCopied] = useState(false);

    function createInvitation(event: FormEvent) {
        event.preventDefault();
        invitationForm.post('/admin/accesos/invitaciones', { onSuccess: () => invitationForm.reset() });
    }

    function updatePassword(event: FormEvent) {
        event.preventDefault();
        passwordForm.post('/admin/accesos/mi-contrasena', { onSuccess: () => passwordForm.reset() });
    }

    async function copyLink() {
        if (!invitationLink) return;
        await navigator.clipboard?.writeText(invitationLink);
        setCopied(true);
    }

    return <>
        <Head title="Accesos" />
        <main className="flow min-h-screen">
            <header className="flow-header"><Link href="/" className="back">← Inicio</Link><span className="brand">Accesos</span></header>
            <p className="eyebrow">Administración</p>
            <h1>Accesos</h1>
            <p className="intro">Invita a personas de confianza y mantén sus accesos al día.</p>
            {notice && <p className="success" role="status">{notice}</p>}
            {invitationLink && <section className="success" aria-label="Enlace de invitación"><strong>Enlace listo para compartir</strong><input className="mt-3 w-full" aria-label="Enlace de invitación" readOnly value={invitationLink} onFocus={(event) => event.currentTarget.select()} /><button className="button secondary mt-3 w-full" type="button" onClick={copyLink}>{copied ? 'Enlace copiado' : 'Copiar enlace'}</button></section>}

            <section className="mt-8" aria-labelledby="invite-title"><h2 id="invite-title">Nueva invitación</h2><form onSubmit={createInvitation} noValidate><div className="field"><label htmlFor="invite-email">Correo exacto</label><input id="invite-email" name="email" type="email" autoComplete="email" placeholder="patrona@ejemplo.com" value={invitationForm.data.email} onChange={(event) => invitationForm.setData('email', event.target.value)} aria-invalid={!!invitationForm.errors.email} required />{invitationForm.errors.email && <p className="error-text" role="alert">{invitationForm.errors.email}</p>}</div><button className="button primary mt-4 w-full" type="submit" disabled={invitationForm.processing}>Crear invitación</button></form></section>

            <section className="mt-8" aria-labelledby="users-title"><h2 id="users-title">Personas con acceso</h2>{users.length ? <ul className="ingredient-list">{users.map((user) => <li key={user.id} className="py-4"><div className="flex items-start justify-between gap-3"><div className="min-w-0"><strong className="block truncate">{user.name}</strong><span className="help block break-words">{user.email}</span><span className="help block">{user.admin ? 'Administradora' : 'Patrona'} · {user.googleLinked ? 'Google vinculado' : 'Sin Google vinculado'} · {user.active ? 'Activo' : 'Inactivo'}</span></div>{user.id !== currentUserId && <button className="button secondary shrink-0 px-3 py-2 text-sm" type="button" onClick={() => router.post(`/admin/accesos/usuarios/${user.id}/estado`, { active: !user.active }, { preserveScroll: true })}>{user.active ? 'Desactivar' : 'Activar'}</button>}</div></li>)}</ul> : <p className="help">Todavía no hay personas con acceso.</p>}</section>

            <section className="mt-8" aria-labelledby="invitations-title"><h2 id="invitations-title">Invitaciones</h2>{invitations.length ? <ul className="ingredient-list">{invitations.map((invitation) => <li key={invitation.id} className="py-4"><div className="flex items-start justify-between gap-3"><div><strong className="block">{invitation.email}</strong><span className="help block">{invitation.status} · creada {invitation.createdAt}{invitation.expiresAt ? ` · vence ${invitation.expiresAt}` : ''}</span></div>{invitation.status === 'Pendiente' && <button className="button secondary shrink-0 px-3 py-2 text-sm" type="button" onClick={() => router.post(`/admin/accesos/invitaciones/${invitation.id}/revocar`, {}, { preserveScroll: true })}>Revocar</button>}</div></li>)}</ul> : <p className="help">Aún no hay invitaciones.</p>}</section>

            <section className="mt-8" aria-labelledby="password-title"><h2 id="password-title">Contraseña de recuperación</h2><p className="help">Solo cambia tu propia contraseña de soporte. Debe tener al menos 12 caracteres.</p><form onSubmit={updatePassword} noValidate><div className="field"><label htmlFor="recovery-password">Nueva contraseña</label><input id="recovery-password" type="password" value={passwordForm.data.password} onChange={(event) => passwordForm.setData('password', event.target.value)} aria-invalid={!!passwordForm.errors.password} required /></div><div className="field"><label htmlFor="recovery-password-confirmation">Confirma la contraseña</label><input id="recovery-password-confirmation" type="password" value={passwordForm.data.password_confirmation} onChange={(event) => passwordForm.setData('password_confirmation', event.target.value)} required />{passwordForm.errors.password && <p className="error-text" role="alert">{passwordForm.errors.password}</p>}</div><button className="button secondary mt-4 w-full" type="submit" disabled={passwordForm.processing}>Guardar contraseña de recuperación</button></form></section>
        </main>
    </>;
}
