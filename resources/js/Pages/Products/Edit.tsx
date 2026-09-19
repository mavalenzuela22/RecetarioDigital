import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, type ChangeEvent, type FormEvent } from 'react';
import { Field, ProductCostCard, ProductShell, type ProductComponent, type ProductCost } from '../../Components/ProductUI';

type Recipe = { id: number; name: string; expected_yield: string };
type Product = { id: number; active: boolean; base_profile_id: number };
type FormComponent = { concept: string; amount: string; allocation: 'batch' | 'unit' | 'order' };
type FormData = { recipe_id: string; base_profile_id: string; active: boolean; reference_order_quantity: string; components: FormComponent[]; request_key: string };
type Props = { product: Product | null; recipe: Recipe; profile: { reference_order_quantity: string; components: FormComponent[] }; cost: ProductCost | null; requestKey: string; storeUrl: string; backUrl: string };

const emptyComponent = (): FormComponent => ({ concept: '', amount: '', allocation: 'unit' });

export default function Edit({ product, recipe, profile, cost, requestKey, storeUrl, backUrl }: Props) {
    const form = useForm<FormData>({
        recipe_id: String(recipe.id),
        base_profile_id: product ? String(product.base_profile_id) : '',
        active: product?.active ?? true,
        reference_order_quantity: profile.reference_order_quantity,
        components: profile.components,
        request_key: requestKey,
    });
    const discard = useRef<HTMLDialogElement>(null);
    const error = (key: string) => (form.errors as Record<string, string | undefined>)[key];

    useEffect(() => {
        const beforeUnload = (event: BeforeUnloadEvent) => { if (form.isDirty) { event.preventDefault(); event.returnValue = ''; } };
        window.addEventListener('beforeunload', beforeUnload);
        return () => window.removeEventListener('beforeunload', beforeUnload);
    }, [form.isDirty]);
    useEffect(() => {
        const firstError = Object.keys(form.errors)[0];
        if (!firstError) return;
        const frame = requestAnimationFrame(() => document.getElementById(firstError)?.focus());
        return () => cancelAnimationFrame(frame);
    }, [form.errors]);

    function submit(event: FormEvent) {
        event.preventDefault();
        if (!form.processing) form.post(storeUrl, { preserveScroll: true, onSuccess: () => { form.reset('request_key'); } });
    }
    function updateComponent(index: number, changes: Partial<FormComponent>) {
        form.setData('components', form.data.components.map((component, componentIndex) => componentIndex === index ? { ...component, ...changes } : component));
    }
    function back() {
        if (form.processing) return;
        if (form.isDirty) { discard.current?.showModal(); } else router.visit(backUrl);
    }

    return <ProductShell title={product ? 'Costos y precio' : 'Configurar producto'} onBack={back}>
        <p className="intro">Producto de <strong>{recipe.name}</strong> · venta por pieza.</p>
        {cost && <ProductCostCard cost={cost} />}
        <form onSubmit={submit} noValidate aria-busy={form.processing}>
            <fieldset disabled={form.processing}>
                <label className="mt-6 flex min-h-12 items-center justify-between gap-3 rounded-2xl border border-ink/10 bg-paper p-4"><span><strong>Producto activo</strong><span className="help block">Si lo desactivas, no aparecerá para nuevos pedidos. Su historia se conserva.</span></span><input type="checkbox" className="size-6" checked={form.data.active} onChange={(event) => form.setData('active', event.target.checked)} /></label>
                {error('active') && <p className="error-text">{error('active')}</p>}
                <section className="mt-8" aria-labelledby="additional-costs-title"><h2 id="additional-costs-title">Costos adicionales</h2><p className="help">Agrega empaque, entrega, gas, mano de obra u otro costo. Cada concepto se aplica una sola vez.</p>{form.data.components.map((component, index) => <div key={index} className="mt-4 rounded-2xl border border-ink/10 bg-paper p-3"><div className="flex items-center justify-between gap-2"><strong>Costo {index + 1}</strong><button type="button" className="min-h-12 px-2 text-sm font-semibold text-primary" onClick={() => form.setData('components', form.data.components.filter((_, itemIndex) => itemIndex !== index))}>Quitar</button></div><Field id={'components.' + index + '.concept'} label="Concepto" error={error('components.' + index + '.concept')}><input id={'components.' + index + '.concept'} value={component.concept} maxLength={120} onChange={(event) => updateComponent(index, { concept: event.target.value })} placeholder="Ej. Empaque" /></Field><div className="quantity-row"><Field id={'components.' + index + '.amount'} label="Importe (MXN)" error={error('components.' + index + '.amount')}><input id={'components.' + index + '.amount'} inputMode="decimal" value={component.amount} onChange={(event) => updateComponent(index, { amount: event.target.value })} placeholder="0.00" /></Field><Field id={'components.' + index + '.allocation'} label="Asignación" error={error('components.' + index + '.allocation')}><select id={'components.' + index + '.allocation'} value={component.allocation} onChange={(event: ChangeEvent<HTMLSelectElement>) => updateComponent(index, { allocation: event.target.value as FormComponent['allocation'] })}><option value="unit">Por pieza</option><option value="batch">Por tanda</option><option value="order">Por pedido</option></select></Field></div></div>)}<button type="button" className="button secondary mt-4 w-full" onClick={() => form.setData('components', [...form.data.components, emptyComponent()])}>+ Agregar costo</button></section>
                {form.data.components.some((component) => component.allocation === 'order') && <Field id="reference_order_quantity" label="Cantidad de referencia del pedido" hint="El costo por pedido se divide entre esta cantidad de piezas." error={error('reference_order_quantity')}><input id="reference_order_quantity" inputMode="numeric" value={form.data.reference_order_quantity} onChange={(event) => form.setData('reference_order_quantity', event.target.value)} /></Field>}
            </fieldset>
            {error('request_key') && <p className="error-message" role="alert">{error('request_key')}</p>}
            {!!Object.keys(form.errors).length && <p className="error-text" role="alert">Revisa los campos señalados. Tus datos siguen aquí.</p>}
            <div className="sticky-actions"><button className="button primary" type="submit" disabled={form.processing}>{form.processing ? 'Guardando configuración…' : 'Guardar configuración'}</button>{form.processing && <p className="help" role="status">Espera mientras confirmamos la nueva versión.</p>}</div>
        </form>
        <dialog ref={discard} aria-labelledby="discard-product-title"><h2 id="discard-product-title">¿Descartar estos cambios?</h2><p>La configuración todavía no se ha guardado.</p><div className="dialog-actions"><button type="button" className="button secondary" autoFocus onClick={() => discard.current?.close()}>Seguir editando</button><button type="button" className="button danger" onClick={() => { discard.current?.close(); router.visit(backUrl); }}>Descartar cambios</button></div></dialog>
    </ProductShell>;
}
