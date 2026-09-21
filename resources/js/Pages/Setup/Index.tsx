import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

export default function Setup() {
    const form = useForm({ secret: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/setup/secret', { onError: () => form.reset('secret') });
    }

    return <>
        <Head title="Configurar administradora" />
        <main className="flow min-h-screen">
            <header className="flow-header"><span className="brand">EmprendimientoOS</span></header>
            <p className="eyebrow">Configuración inicial</p>
            <h1>Crear la primera administradora</h1>
            <p className="intro">Escribe el secreto de configuración y después continúa con Google. Este paso solo está disponible una vez.</p>
            <form onSubmit={submit} noValidate>
                <div className="field"><label htmlFor="secret">Secreto de configuración</label><input id="secret" name="secret" type="password" autoComplete="off" value={form.data.secret} onChange={(event) => form.setData('secret', event.target.value)} aria-invalid={!!form.errors.secret} required />{form.errors.secret && <p className="error-text" role="alert">{form.errors.secret}</p>}</div>
                <button className="button primary mt-6 w-full" type="submit" disabled={form.processing}>{form.processing ? 'Comprobando…' : 'Continuar con Google'}</button>
            </form>
        </main>
    </>;
}
