import { router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState, type FormEvent } from 'react';
import { Field } from '../../Components/PurchaseUI';
import { decimalInputFromMinor, formatMinor, lineRevenue, minorFromDecimal, orderTotal, OrderShell } from '../../Components/OrderUI';
import type { OrderLineInput, OrderProduct } from '../../Components/OrderUI';

type Props = { products: OrderProduct[]; storeUrl: string; indexUrl: string; requestKey: string; defaultDate: string; defaultTime: string };
type OrderForm = { customer_name: string; lines: OrderLineInput[]; delivery_date: string; delivery_time: string; notes: string; advance: string; request_key: string };

export default function Create({ products, storeUrl, indexUrl, requestKey, defaultDate, defaultTime }: Props) {
    const form = useForm<OrderForm>({ customer_name: '', lines: [], delivery_date: defaultDate, delivery_time: defaultTime, notes: '', advance: '0', request_key: requestKey });
    const [selectedProduct, setSelectedProduct] = useState('');
    const discard = useRef<HTMLDialogElement>(null);
    const allowLeave = useRef(false);
    const saving = useRef(false);
    const total = useMemo(() => orderTotal(form.data.lines), [form.data.lines]);
    const paid = minorFromDecimal(form.data.advance) ?? 0n;
    const balance = total - paid;
    const dirty = form.isDirty;

    useEffect(() => {
        const beforeUnload = (event: BeforeUnloadEvent) => { if (dirty && !allowLeave.current) { event.preventDefault(); event.returnValue = ''; } };
        window.addEventListener('beforeunload', beforeUnload);
        return () => window.removeEventListener('beforeunload', beforeUnload);
    }, [dirty]);

    function back() {
        if (saving.current) return;
        if (dirty) discard.current?.showModal();
        else router.visit(indexUrl);
    }

    function addProduct() {
        const product = products.find((item) => String(item.id) === selectedProduct);
        if (!product || form.data.lines.some((line) => line.product_id === product.id)) return;
        form.setData('lines', [...form.data.lines, { product_id: product.id, product_name: product.name, sale_unit: product.sale_unit, quantity: '1', agreed_price: product.current_price_minor ? decimalInputFromMinor(product.current_price_minor) : '' }]);
        setSelectedProduct('');
    }

    function updateLine(index: number, changes: Partial<OrderLineInput>) {
        form.setData('lines', form.data.lines.map((line, position) => position === index ? { ...line, ...changes } : line));
    }

    function changeQuantity(index: number, delta: 1 | -1) {
        const current = form.data.lines[index];
        if (!current || !/^[1-9][0-9]{0,8}$/.test(current.quantity)) return;
        const next = BigInt(current.quantity) + BigInt(delta);
        if (next >= 1n) updateLine(index, { quantity: next.toString() });
    }

    function removeLine(index: number) {
        form.setData('lines', form.data.lines.filter((_, position) => position !== index));
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        if (saving.current || unavailableLines.length > 0) return;
        saving.current = true;
        form.post(storeUrl, { preserveState: true, preserveScroll: true, onSuccess: () => { allowLeave.current = true; }, onFinish: () => { saving.current = false; }, onError: () => requestAnimationFrame(() => document.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus()) });
    }

    const unavailableLines = form.data.lines.filter((line) => !products.some((product) => product.id === line.product_id));

    return <OrderShell title="Tomar pedido" onBack={back}>
        <p className="intro">Captura lo que acordaste y guarda el precio de este pedido tal como está hoy.</p>
        <form onSubmit={submit}>
            <Field id="customer_name" label="Cliente" error={form.errors.customer_name}>
                <input id="customer_name" value={form.data.customer_name} onChange={(event) => form.setData('customer_name', event.target.value)} aria-invalid={!!form.errors.customer_name} autoComplete="name" />
            </Field>

            <section className="mt-8" aria-labelledby="products-title">
                <h2 id="products-title">Productos</h2>
                <div className="flex flex-col gap-2 sm:flex-row">
                    <select className="min-h-[52px] min-w-0 flex-1 rounded-xl border border-[var(--eo-input-border)] bg-paper px-3" aria-label="Producto para agregar" value={selectedProduct} onChange={(event) => setSelectedProduct(event.target.value)}>
                        <option value="">Elige un producto</option>
                        {products.map((product) => <option key={product.id} value={product.id}>{product.name}{product.current_price_minor ? ` · ${formatMinor(BigInt(product.current_price_minor))}` : ''}</option>)}
                    </select>
                    <div className="flex w-full shrink-0 gap-2 sm:w-auto"><button className="button secondary min-w-0 flex-1" type="button" onClick={addProduct} disabled={!selectedProduct}>Agregar producto</button><button className="button secondary min-w-0 flex-1" type="button" onClick={() => router.reload({ only: ['products'] })}>Actualizar catálogo</button></div>
                </div>
                {form.errors.lines && <p className="error-text" role="alert">{form.errors.lines}</p>}
                {unavailableLines.length > 0 && <p className="error-text" role="alert">Hay {unavailableLines.length === 1 ? 'un producto' : 'productos'} que ya no está disponible para nuevos pedidos. Revisa {unavailableLines.length === 1 ? 'la línea' : 'las líneas'} marcada{s(unavailableLines.length)} y quítala{s(unavailableLines.length)} para continuar.</p>}
                {!form.data.lines.length && <p className="help">Agrega al menos un producto.</p>}
                <div className="mt-4 space-y-4">{form.data.lines.map((line, index) => {
                    const product = products.find((item) => item.id === line.product_id);
                    const productName = product?.name ?? line.product_name ?? 'Producto no disponible';
                    const revenue = lineRevenue(line);
                    return <article key={line.product_id} className="rounded-2xl border border-[var(--eo-line)] bg-paper p-4">
                        <div className="flex items-start justify-between gap-3"><div><h3 className="font-semibold">{productName}</h3><p className="help">MXN · El precio acordado se conserva en este pedido.</p>{!product && <p className="mt-2 error-text" role="status">Este producto ya no está disponible para nuevos pedidos. Quita esta línea para continuar.</p>}</div><button className="back shrink-0" type="button" onClick={() => removeLine(index)}>Quitar</button></div>
                        <div className="mt-3 grid grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] gap-3">
                            <Field id={`lines.${index}.quantity`} label="Cantidad" error={form.errors[`lines.${index}.quantity`]}>
                                <div className="flex gap-1"><button className="button secondary min-w-12 px-2" type="button" aria-label={`Disminuir cantidad de ${productName}`} onClick={() => changeQuantity(index, -1)}>−</button><input id={`lines.${index}.quantity`} name={`lines.${index}.quantity`} inputMode="numeric" value={line.quantity} onChange={(event) => updateLine(index, { quantity: event.target.value })} aria-label={`Cantidad ${productName}`} aria-invalid={!!form.errors[`lines.${index}.quantity`]} /><button className="button secondary min-w-12 px-2" type="button" aria-label={`Aumentar cantidad de ${productName}`} onClick={() => changeQuantity(index, 1)}>+</button></div>
                            </Field>
                            <Field id={`lines.${index}.agreed_price`} label="Precio acordado" error={form.errors[`lines.${index}.agreed_price`]}>
                                <input id={`lines.${index}.agreed_price`} name={`lines.${index}.agreed_price`} inputMode="decimal" value={line.agreed_price} onChange={(event) => updateLine(index, { agreed_price: event.target.value })} aria-label={`Precio acordado ${productName}`} aria-invalid={!!form.errors[`lines.${index}.agreed_price`]} />
                            </Field>
                        </div>
                        <p className="mt-2 text-right text-sm font-semibold">{revenue === null ? '—' : formatMinor(revenue)}</p>
                    </article>;
                })}</div>
            </section>

            <div className="grid gap-1 sm:grid-cols-2 sm:gap-3">
                <Field id="delivery_date" label="Fecha de entrega" error={form.errors.delivery_date}><input id="delivery_date" type="date" value={form.data.delivery_date} onChange={(event) => form.setData('delivery_date', event.target.value)} aria-invalid={!!form.errors.delivery_date} /></Field>
                <Field id="delivery_time" label="Hora" error={form.errors.delivery_time}><input id="delivery_time" type="time" value={form.data.delivery_time} onChange={(event) => form.setData('delivery_time', event.target.value)} aria-invalid={!!form.errors.delivery_time} /></Field>
            </div>
            <Field id="notes" label="Nota (opcional)" error={form.errors.notes}><textarea id="notes" rows={3} value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} aria-invalid={!!form.errors.notes} /></Field>
            <Field id="advance" label="Anticipo recibido" hint="Déjalo en $0 si todavía no recibiste pago." error={form.errors.advance}><input id="advance" inputMode="decimal" value={form.data.advance} onChange={(event) => form.setData('advance', event.target.value)} aria-invalid={!!form.errors.advance} /></Field>

            <section className="cost-summary" aria-label="Resumen del pedido">
                <p className="eyebrow">Cierre del pedido</p>
                <dl><div><dt>Total del pedido</dt><dd>{formatMinor(total)}</dd></div><div><dt>Anticipo</dt><dd>{formatMinor(paid)}</dd></div><div><dt>Saldo pendiente</dt><dd>{formatMinor(balance)}</dd></div></dl>
                {paid > total && <p className="error-text" role="alert">El anticipo debe estar entre $0 y el total.</p>}
            </section>
            <div className="sticky-actions"><button className="button primary" type="submit" disabled={form.processing || unavailableLines.length > 0}>{form.processing ? 'Guardando pedido…' : 'Guardar pedido'}</button></div>
        </form>
        <dialog ref={discard} aria-labelledby="discard-title"><h2 id="discard-title">¿Descartar este pedido?</h2><p>Los datos que escribiste todavía no se han guardado.</p><div className="dialog-actions"><button type="button" className="button secondary" autoFocus onClick={() => discard.current?.close()}>Seguir editando</button><button type="button" className="button danger" onClick={() => { allowLeave.current = true; discard.current?.close(); router.visit(indexUrl); }}>Descartar cambios</button></div></dialog>
    </OrderShell>;
}

function s(count: number): string {
    return count === 1 ? '' : 's';
}
