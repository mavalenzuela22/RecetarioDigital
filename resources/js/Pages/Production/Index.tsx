import { Link, router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { AppShell, Field } from '../../Components/PurchaseUI';
import { FulfillmentStatus } from '../../Components/OrderUI';

type MoneySummary = { expected_revenue_label: string; cost_complete: boolean; estimated_cost_label: string | null; estimated_profit_label: string | null; profit_pending_label: string | null };
type ProductionOrder = { id: number; customer_name: string; product_name: string | null; delivery_date_label: string; delivery_time: string; quantity: string; fulfillment_state: string; url: string; ready_url: string | null; ready_request_key: string | null };
type Group = { product_id: number; name: string; total_units: string; units_to_prepare: string; ready_units: string; orders: ProductionOrder[] };

export default function Production({ title, from, to, from_label, to_label, has_confirmed_orders, has_orders, groups, money, backUrl, productionUrl, startUrl, success }: {
    title: string; from: string; to: string; from_label: string; to_label: string; has_confirmed_orders: boolean; has_orders: boolean; groups: Group[]; money: MoneySummary; backUrl: string; productionUrl: string; startUrl: string; success?: string;
}) {
    const dates = useForm({ from, to });
    const start = useForm({ from, to });
    const [readyId, setReadyId] = useState<number | null>(null);
    const submitDates = (event: FormEvent) => { event.preventDefault(); dates.get(productionUrl, { preserveState: true, preserveScroll: true }); };
    const startProduction = (event: FormEvent) => { event.preventDefault(); if (!start.processing) start.post(startUrl, { preserveScroll: true }); };
    const markReady = (order: ProductionOrder) => {
        if (!order.ready_url || !order.ready_request_key || readyId !== null) return;
        setReadyId(order.id);
        router.post(order.ready_url, { request_key: order.ready_request_key, from, to }, { preserveScroll: true, onFinish: () => setReadyId(null) });
    };

    return <AppShell title={title} back={backUrl}>
        {success && <p className="success" role="status">{success}</p>}
        <p className="intro">Pedidos activos del {from_label}{from === to ? '' : ` al ${to_label}`}.</p>
        <form onSubmit={submitDates} className="rounded-2xl bg-paper p-4" aria-label="Rango de producción"><div className="grid gap-3 sm:grid-cols-2"><Field id="from" label="Desde"><input id="from" name="from" type="date" value={dates.data.from} onChange={(event) => dates.setData('from', event.target.value)} /></Field><Field id="to" label="Hasta"><input id="to" name="to" type="date" value={dates.data.to} onChange={(event) => dates.setData('to', event.target.value)} /></Field></div><button className="button secondary mt-4 w-full" type="submit">Actualizar fechas</button></form>

        {!has_orders ? <section className="empty" aria-label="Producción vacía"><h2>No hay pedidos por preparar en esta fecha.</h2><p className="help">Elige otro día o registra un pedido nuevo.</p></section> : <>
            <section className="cost-summary" aria-label="Resumen de producción"><p className="eyebrow">Economía del rango</p><dl><div><dt>Venta estimada</dt><dd>{money.expected_revenue_label}</dd></div>{money.cost_complete && <div><dt>Costo estimado</dt><dd>{money.estimated_cost_label}</dd></div>}<div><dt>Ganancia estimada</dt><dd>{money.estimated_profit_label ?? money.profit_pending_label}</dd></div></dl>{!money.cost_complete && <p className="help">Venta estimada disponible. Costo y ganancia pendientes por ingredientes sin costo.</p>}</section>
            <section aria-labelledby="groups-title"><h2 id="groups-title">Pedidos y piezas</h2>{groups.map((group) => <article key={group.product_id} className="purchase-card"><div className="flex min-w-0 flex-wrap items-start justify-between gap-3"><div className="min-w-0"><h3 className="break-words">{group.name}</h3><p className="help">{group.units_to_prepare} por preparar · {group.ready_units} listos · {group.total_units} en total</p></div></div><ul className="mt-3 divide-y divide-ink/10">{group.orders.map((order) => <li key={order.id} className="py-3"><div className="flex min-w-0 items-start justify-between gap-3"><Link href={order.url} className="min-w-0"><strong className="block truncate">{order.customer_name}</strong><span className="help block">{order.quantity} piezas · {order.delivery_date_label} · {order.delivery_time}</span></Link><FulfillmentStatus state={order.fulfillment_state} /></div>{order.fulfillment_state === 'in_preparation' && <button className="button secondary mt-3 w-full" type="button" onClick={() => markReady(order)} disabled={readyId !== null}>{readyId === order.id ? 'Guardando…' : 'Marcar listo'}</button>}</li>)}</ul></article>)}</section>
            {has_confirmed_orders && <form onSubmit={startProduction}><input type="hidden" name="from" value={start.data.from} /><input type="hidden" name="to" value={start.data.to} /><button className="button primary mt-2 w-full" type="submit" disabled={start.processing}>{start.processing ? 'Iniciando…' : 'Iniciar preparación'}</button></form>}
        </>}
    </AppShell>;
}
