import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import { AppShell, Field, perUnit, type Ingredient } from '../../Components/PurchaseUI';

type Props = { ingredients: Ingredient[]; selectedIngredient: string; requestKey: string; storeUrl: string; indexUrl: string };
function localDate() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}
export default function Create({ ingredients, selectedIngredient, requestKey, storeUrl, indexUrl }: Props) {
    const initial = ingredients.find((ingredient) => ingredient.name === selectedIngredient);
    const form = useForm({ ingredient_name: selectedIngredient, presentation: '', purchase_quantity: '', purchase_unit: initial?.canonical_unit ?? 'kg', total_paid: '', purchased_on: localDate(), store: '', note: '', request_key: requestKey });
    const [optional, setOptional] = useState(false);
    const [networkError, setNetworkError] = useState('');
    const discard = useRef<HTMLDialogElement>(null);
    const allowLeave = useRef(false);
    const saving = useRef(false);
    const selected = ingredients.find((ingredient) => ingredient.name.trim().toLocaleLowerCase('es-MX') === form.data.ingredient_name.trim().toLocaleLowerCase('es-MX'));
    const canonical = selected?.canonical_unit ?? ({ kg: 'g', l: 'ml' }[form.data.purchase_unit] ?? form.data.purchase_unit);
    useEffect(() => {
        const beforeUnload = (event: BeforeUnloadEvent) => { if (form.isDirty && !allowLeave.current) { event.preventDefault(); event.returnValue = ''; } };
        window.addEventListener('beforeunload', beforeUnload);
        return () => window.removeEventListener('beforeunload', beforeUnload);
    }, [form.isDirty]);
    useEffect(() => router.on('exception', (event) => {
        event.preventDefault();
        saving.current = false;
        setNetworkError('No pudimos confirmar si se guardó la compra. Tus datos siguen aquí. Reintenta con este mismo formulario para evitar duplicados.');
    }), []);
    useEffect(() => {
        const firstError = Object.keys(form.errors)[0];
        if (!firstError) return;
        if ((firstError === 'store' || firstError === 'note') && !optional) {
            setOptional(true);
            return;
        }
        const frame = requestAnimationFrame(() => document.getElementById(firstError)?.focus());
        return () => cancelAnimationFrame(frame);
    }, [form.errors, optional]);
    function back() {
        if (saving.current) return;
        if (form.isDirty) discard.current?.showModal();
        else router.visit(indexUrl);
    }
    function submit(event: FormEvent) {
        event.preventDefault();
        if (saving.current) return;
        saving.current = true;
        setNetworkError('');
        form.post(storeUrl, {
            preserveScroll: true,
            onSuccess: () => { allowLeave.current = true; },
            onFinish: () => { saving.current = false; },
        });
    }
    const attrs = (key: keyof typeof form.data, hint = false) => ({
        id: key, name: key, value: form.data[key], disabled: form.processing,
        'aria-invalid': !!form.errors[key],
        'aria-describedby': [hint ? `${key}-hint` : '', form.errors[key] ? `${key}-error` : ''].filter(Boolean).join(' ') || undefined,
        onChange: (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => form.setData(key, event.target.value),
    });
    return <AppShell title="Registrar compra" onBack={back}>
        <p className="intro">Cuéntanos qué compraste. Nosotros calculamos el costo por unidad.</p>
        <form onSubmit={submit} noValidate aria-busy={form.processing}>
            <fieldset disabled={form.processing}>
                <Field id="ingredient_name" label="Ingrediente" hint="Escribe un nombre o elige uno que ya usas. Máximo 120 caracteres." error={form.errors.ingredient_name}>
                    <input {...attrs('ingredient_name', true)} list="ingredients" autoComplete="off" maxLength={120} required placeholder="Ej. Harina de trigo" />
                    <datalist id="ingredients">{ingredients.map((ingredient) => <option key={ingredient.id} value={ingredient.name} />)}</datalist>
                </Field>
                <p className="help" role="status">{selected ? `Ya registrado: se mide por ${perUnit(selected.canonical_unit)}.` : 'Si es nuevo, lo agregaremos con esta compra.'}</p>
                <Field id="presentation" label="Presentación" hint="Describe el empaque; por ejemplo, bolsa de 1 kg. Máximo 120 caracteres." error={form.errors.presentation}>
                    <input {...attrs('presentation', true)} maxLength={120} required placeholder="Ej. Bolsa de 1 kg" />
                </Field>
                <div className="quantity-row">
                    <Field id="purchase_quantity" label="Cantidad comprada" error={form.errors.purchase_quantity} hint="Contenido total, no número de paquetes. Hasta 3 decimales.">
                        <input {...attrs('purchase_quantity', true)} inputMode="decimal" required placeholder="1" />
                    </Field>
                    <Field id="purchase_unit" label="Unidad" error={form.errors.purchase_unit}>
                        <select {...attrs('purchase_unit')}><option value="kg">kg</option><option value="g">g</option><option value="l">l</option><option value="ml">ml</option><option value="piece">pieza</option></select>
                    </Field>
                </div>
                <Field id="total_paid" label="Total pagado (MXN)" hint="Hasta 2 decimales, sin separador de miles. Ej. 42.00 o 42,50." error={form.errors.total_paid}>
                    <input {...attrs('total_paid', true)} inputMode="decimal" required placeholder="42.00" />
                </Field>
                <Field id="purchased_on" label="Fecha de compra" error={form.errors.purchased_on}>
                    <input {...attrs('purchased_on')} type="date" required min="1000-01-01" max="9999-12-31" />
                </Field>
                <button type="button" className="optional-toggle" aria-expanded={optional} aria-controls="optional-fields" onClick={() => setOptional(!optional)}>{optional ? '− Ocultar' : '+ Agregar'} tienda o nota <span className="help">(opcional)</span></button>
                <div id="optional-fields" hidden={!optional}>
                    <Field id="store" label="Tienda o proveedor" hint="Opcional. Máximo 120 caracteres." error={form.errors.store}><input {...attrs('store', true)} maxLength={120} /></Field>
                    <Field id="note" label="Nota" hint={`${form.data.note.length}/2000 caracteres. Opcional.`} error={form.errors.note}><textarea {...attrs('note', true)} rows={3} maxLength={2000} /></Field>
                </div>
            </fieldset>
            <aside className="cost-summary"><h2>El costo, sin hacer cuentas</h2><p>Calcularemos cuánto pagaste por {perUnit(canonical)} al guardar.</p><p className="help">1 kg equivale a 1,000 g · 1 l equivale a 1,000 ml. Tu compra conservará la cantidad y el total originales.</p></aside>
            {form.errors.request_key && <p id="request_key" tabIndex={-1} className="error-message" role="alert">{form.errors.request_key}</p>}
            {networkError && <p className="error-message" role="alert">{networkError}</p>}
            {!!Object.keys(form.errors).length && <p role="alert" className="error-text">Revisa los campos señalados. Tus datos siguen aquí.</p>}
            <div className="sticky-actions"><button className="button primary" type="submit" disabled={form.processing}>{form.processing ? 'Guardando compra…' : networkError ? 'Reintentar' : 'Guardar compra'}</button>{form.processing && <p className="help" role="status">Espera mientras confirmamos el registro.</p>}</div>
        </form>
        <dialog ref={discard} aria-labelledby="discard-title"><h2 id="discard-title">¿Descartar esta compra?</h2><p>Los datos que escribiste todavía no se han guardado.</p><div className="dialog-actions"><button type="button" className="button secondary" autoFocus onClick={() => discard.current?.close()}>Seguir editando</button><button type="button" className="button danger" onClick={() => { allowLeave.current = true; discard.current?.close(); router.visit(indexUrl); }}>Descartar cambios</button></div></dialog>
    </AppShell>;
}
