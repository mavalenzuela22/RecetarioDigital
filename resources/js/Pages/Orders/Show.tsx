import { useForm } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import { dateLabel, Field } from '../../Components/PurchaseUI';
import { decimalInputFromMinor, FulfillmentStatus, formatMinor, OrderMoney, OrderShell, PaymentStatus } from '../../Components/OrderUI';

type Line = { id: number; product_name: string; quantity: string; sale_unit: string; agreed_unit_price_minor: string; line_revenue_minor: string; attributable_unit_cost_micros: string | null; attributable_line_cost_micros: string | null; cost_complete: boolean };
type Payment = { id: number; kind: string; amount_minor: string; payment_date: string | null };
type Order = { id: number; customer_name: string; delivery_date: string; delivery_time: string; notes: string | null; fulfillment_state: string; payment_state: string; total_minor: string; paid_minor: string; balance_minor: string; lines: Line[]; payments: Payment[]; profit: { complete: boolean; total_cost_micros: string | null; profit_micros: string | null } };
type PaymentForm = { amount: string; payment_date: string; request_key: string };
type TransitionForm = { request_key: string };

export default function Show({ order, success, indexUrl, paymentUrl, deliveryUrl, cancellationUrl, paymentRequestKey, deliveryRequestKey, cancellationRequestKey, defaultPaymentDate }: { order: Order; success?: string; indexUrl: string; paymentUrl: string; deliveryUrl: string; cancellationUrl: string; paymentRequestKey: string; deliveryRequestKey: string; cancellationRequestKey: string; defaultPaymentDate: string }) {
    const paymentForm = useForm<PaymentForm>({ amount: decimalInputFromMinor(order.balance_minor), payment_date: defaultPaymentDate, request_key: paymentRequestKey });
    const deliveryForm = useForm<TransitionForm>({ request_key: deliveryRequestKey });
    const cancellationForm = useForm<TransitionForm>({ request_key: cancellationRequestKey });
    const [dialog, setDialog] = useState<'payment' | 'delivery' | 'cancellation' | null>(null);
    const dialogRef = useRef<HTMLDialogElement>(null);
    const canTransition = ['confirmed', 'in_preparation', 'ready'].includes(order.fulfillment_state);

    useEffect(() => {
        if (dialog) dialogRef.current?.showModal();
        else if (dialogRef.current?.open) dialogRef.current.close();
    }, [dialog]);

    function closeDialog() {
        dialogRef.current?.close();
        setDialog(null);
    }

    function openPayment() {
        paymentForm.setData('amount', decimalInputFromMinor(order.balance_minor));
        paymentForm.setData('payment_date', defaultPaymentDate);
        paymentForm.setData('request_key', paymentRequestKey);
        paymentForm.clearErrors();
        setDialog('payment');
    }

    function submitPayment(event: FormEvent) {
        event.preventDefault();
        if (paymentForm.processing) return;
        paymentForm.post(paymentUrl, { preserveState: true, preserveScroll: true, onSuccess: closeDialog, onError: () => requestAnimationFrame(() => document.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus()) });
    }

    function confirmTransition() {
        if (dialog === 'delivery') {
            if (deliveryForm.processing) return;
            deliveryForm.post(deliveryUrl, { preserveState: true, preserveScroll: true, onSuccess: closeDialog });
        }
        if (dialog === 'cancellation') {
            if (cancellationForm.processing) return;
            cancellationForm.post(cancellationUrl, { preserveState: true, preserveScroll: true, onSuccess: closeDialog });
        }
    }

    return <OrderShell title="Cobro y entrega" back={indexUrl}>
        {success && <p className="success" role="status">{success}</p>}
        <div className="flex flex-wrap gap-2"><FulfillmentStatus state={order.fulfillment_state} /><PaymentStatus state={order.payment_state} /></div>
        <section className="mt-6 rounded-2xl border border-ink/10 bg-paper p-4"><p className="help">Cliente</p><h2>{order.customer_name}</h2><p>{dateLabel(order.delivery_date)} · {order.delivery_time}</p>{order.notes && <p className="metadata">{order.notes}</p>}</section>
        <p className="help mt-4">Entregar no cambia el pago. Cobrar no cambia la entrega.</p>

        <section className="mt-8" aria-labelledby="lines-title"><h2 id="lines-title">Productos</h2><ul className="ingredient-list">{order.lines.map((line) => <li key={line.id} className="py-4"><div className="flex flex-wrap items-start justify-between gap-2"><div><strong>{line.product_name}</strong><p className="help">{line.quantity} {line.sale_unit === 'piece' ? 'piezas' : line.sale_unit} · Precio acordado <OrderMoney value={line.agreed_unit_price_minor} /></p></div><strong><OrderMoney value={line.line_revenue_minor} /></strong></div></li>)}</ul></section>
        <section className="cost-summary" aria-label="Resumen del pedido"><dl><div><dt>Total del pedido</dt><dd><OrderMoney value={order.total_minor} /></dd></div><div><dt>Recibido</dt><dd><OrderMoney value={order.paid_minor} /></dd></div><div><dt>Saldo pendiente</dt><dd><OrderMoney value={order.balance_minor} /></dd></div></dl></section>

        {order.balance_minor !== '0' && order.fulfillment_state !== 'cancelled' ? <button className="button primary w-full" type="button" onClick={openPayment}>Registrar cobro</button> : order.payment_state === 'paid' ? <p className="success" role="status">Pedido pagado</p> : null}
        {order.fulfillment_state === 'delivered' && <p className="success" role="status">Entrega registrada</p>}
        {canTransition && <div className="mt-3 grid gap-3 sm:grid-cols-2"><button className="button secondary" type="button" onClick={() => setDialog('delivery')}>Marcar como entregado</button><button className="button danger" type="button" onClick={() => setDialog('cancellation')}>Cancelar pedido</button></div>}

        <section className="history" aria-labelledby="payments-title"><h2 id="payments-title">Pagos registrados</h2>{order.payments.length ? <ul className="history-list">{order.payments.map((payment) => <li key={payment.id} className="flex flex-wrap justify-between gap-2 border-b border-ink/10 py-3"><span><strong>{payment.kind === 'advance' ? 'Anticipo' : 'Cobro'}</strong><span className="help block">{payment.payment_date ? dateLabel(payment.payment_date) : 'Registrado al guardar el pedido'}</span></span><strong><OrderMoney value={payment.amount_minor} /></strong></li>)}</ul> : <p className="help">Aún no hay pagos registrados.</p>}</section>

        <section className="mt-8 rounded-2xl border border-ink/10 bg-paper p-4" aria-label="Ganancia histórica"><h2>Economía del pedido</h2>{order.profit.complete ? <dl><div><dt>Costo atribuido</dt><dd><OrderMoney value={order.profit.total_cost_micros} unit="micros" /></dd></div><div><dt>Ganancia estimada</dt><dd><OrderMoney value={order.profit.profit_micros} unit="micros" /></dd></div></dl> : <p>Ganancia pendiente de calcular.</p>}</section>

        {dialog === 'payment' && <dialog ref={dialogRef} aria-labelledby="payment-dialog-title" onCancel={closeDialog}><h2 id="payment-dialog-title">Registrar cobro</h2><p className="help">Saldo actual: {formatMinor(BigInt(order.balance_minor))}</p><form onSubmit={submitPayment}><Field id="payment_amount" label="Importe recibido" error={paymentForm.errors.amount}><input id="payment_amount" inputMode="decimal" value={paymentForm.data.amount} onChange={(event) => paymentForm.setData('amount', event.target.value)} aria-invalid={!!paymentForm.errors.amount} autoFocus /></Field><Field id="payment_date" label="Fecha del cobro" error={paymentForm.errors.payment_date}><input id="payment_date" type="date" value={paymentForm.data.payment_date} onChange={(event) => paymentForm.setData('payment_date', event.target.value)} aria-invalid={!!paymentForm.errors.payment_date} /></Field><div className="dialog-actions mt-6"><button className="button primary" type="submit" disabled={paymentForm.processing}>{paymentForm.processing ? 'Guardando cobro…' : 'Registrar cobro'}</button><button className="button secondary" type="button" onClick={closeDialog}>Cancelar</button></div></form></dialog>}
        {dialog === 'delivery' && <dialog ref={dialogRef} aria-labelledby="delivery-dialog-title" onCancel={closeDialog}><h2 id="delivery-dialog-title">Confirmar entrega</h2><p>¿Entregaste este pedido a {order.customer_name}?</p><p className="help">El saldo pendiente se conserva hasta que registres el cobro.</p><div className="dialog-actions"><button className="button primary" type="button" onClick={confirmTransition} disabled={deliveryForm.processing}>{deliveryForm.processing ? 'Guardando…' : 'Sí, ya entregué'}</button><button className="button secondary" type="button" onClick={closeDialog}>Todavía no</button></div></dialog>}
        {dialog === 'cancellation' && <dialog ref={dialogRef} aria-labelledby="cancellation-dialog-title" onCancel={closeDialog}><h2 id="cancellation-dialog-title">Cancelar pedido</h2><p>Este pedido dejará de estar pendiente de trabajo, pero conservará sus líneas, precios y pagos.</p>{order.paid_minor !== '0' && <p className="error-message">Cancelar el pedido no registra una devolución.</p>}<div className="dialog-actions"><button className="button danger" type="button" onClick={confirmTransition} disabled={cancellationForm.processing}>{cancellationForm.processing ? 'Cancelando…' : 'Sí, cancelar pedido'}</button><button className="button secondary" type="button" onClick={closeDialog}>Conservar pedido</button></div></dialog>}
    </OrderShell>;
}
