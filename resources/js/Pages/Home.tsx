import { Head, Link, router } from '@inertiajs/react';

type Money = { expected_revenue_label: string; balance_label: string; cost_complete: boolean; estimated_cost_label: string | null; estimated_profit_label: string | null; profit_pending_label: string | null };
type Group = { product_id: number; name: string; total_units: string; units_to_prepare: string; ready_units: string; orders: unknown[] };
type Delivery = { id: number; customer_name: string; delivery_time: string; fulfillment_label: string; url: string };
type Collection = { id: number; customer_name: string; balance_label: string; url: string };

export default function Home({ title, date_label, production, deliveries, collections, money, productionUrl, orderUrl, purchaseUrl, isAdmin = false, accessUrl }: {
    title: string; date_label: string; production: { units_to_prepare: string; groups: Group[] }; deliveries: Delivery[]; collections: Collection[]; money: Money; productionUrl: string; orderUrl: string; purchaseUrl: string; isAdmin?: boolean; accessUrl?: string;
}) {
    return <>
        <Head title="Hoy" />
        <main className="flow min-h-screen">
            <header className="flow-header"><Link href="/" className="brand" aria-label="EmprendimientoOS, inicio">EmprendimientoOS</Link><div className="flex items-center gap-3"><span className="help m-0">{date_label}</span><button className="text-sm font-semibold text-primary" type="button" aria-label="Cerrar sesión" onClick={() => router.post('/logout')}>Salir</button></div></header>
            <p className="eyebrow">Tu jornada</p><h1>{title}</h1><p className="intro">Lo que necesitas preparar, entregar y cobrar hoy.</p>

            {!production.groups.length && !deliveries.length && !collections.length
                ? <section className="empty rounded-2xl bg-paper" aria-label="Día sin pedidos"><h2>No tienes pedidos para hoy.</h2><p className="help">Puedes comenzar con un pedido nuevo.</p><Link className="button primary mt-4 w-full" href={orderUrl}>Tomar pedido</Link></section>
                : <>
                    <section aria-labelledby="preparation-title" className="rounded-2xl bg-paper p-4 shadow-[var(--eo-shadow-raised)]"><div className="flex flex-wrap items-start justify-between gap-3"><div><p className="help m-0">Necesitas preparar</p><h2 id="preparation-title" className="m-0">{production.units_to_prepare} piezas</h2></div><Link href={productionUrl} className="button secondary min-h-12 px-3 py-2 text-sm">Ver producción</Link></div>{production.groups.length ? <ul className="ingredient-list mb-0"><li className="py-3">{production.groups.slice(0, 3).map((group) => <div key={group.product_id} className="flex min-w-0 items-center justify-between gap-3 py-2"><span className="min-w-0 truncate font-semibold">{group.name}</span><span className="shrink-0 text-sm text-ink/65">{group.units_to_prepare} piezas</span></div>)}</li></ul> : <p className="help mb-0">No hay piezas pendientes de preparación.</p>}</section>
                    <section className="mt-8" aria-labelledby="deliveries-title"><div className="flex items-center justify-between gap-3"><h2 id="deliveries-title">Entregas pendientes</h2><span className="help m-0">{deliveries.length}</span></div>{deliveries.length ? <ul className="ingredient-list">{deliveries.map((delivery) => <li key={delivery.id}><Link href={delivery.url} className="ingredient-row"><span className="flex min-w-0 items-center justify-between gap-3"><strong className="truncate">{delivery.customer_name}</strong><span className="shrink-0 text-sm">{delivery.delivery_time}</span></span><span className="help m-0">{delivery.fulfillment_label}</span></Link></li>)}</ul> : <p className="help">No hay entregas pendientes.</p>}</section>
                    <section className="mt-8" aria-labelledby="collections-title"><div className="flex items-center justify-between gap-3"><h2 id="collections-title">Por cobrar hoy</h2><span className="help m-0">{collections.length}</span></div>{collections.length ? <ul className="ingredient-list">{collections.map((collection) => <li key={collection.id}><Link href={collection.url} className="ingredient-row"><span className="flex min-w-0 items-center justify-between gap-3"><strong className="truncate">{collection.customer_name}</strong><strong className="shrink-0">{collection.balance_label}</strong></span><span className="help m-0">Abrir Cobro y entrega</span></Link></li>)}</ul> : <p className="help">No hay saldos pendientes.</p>}</section>
                </>}

            <section className="cost-summary" aria-label="Resumen de dinero de hoy"><p className="eyebrow">Dinero estimado del día</p><dl><div><dt>Venta estimada</dt><dd>{money.expected_revenue_label}</dd></div><div><dt>Saldo pendiente</dt><dd>{money.balance_label}</dd></div>{money.cost_complete && <div><dt>Costo estimado</dt><dd>{money.estimated_cost_label}</dd></div>}<div><dt>Ganancia estimada</dt><dd>{money.estimated_profit_label ?? money.profit_pending_label}</dd></div></dl>{!money.cost_complete && <p className="help">Se muestra venta y saldo; la ganancia queda pendiente porque falta un costo histórico.</p>}</section>
            <div className="grid gap-3 pb-6 sm:grid-cols-2"><Link className="button primary" href={productionUrl}>Ver producción</Link><Link className="button secondary" href={purchaseUrl}>Registrar compra</Link><Link className="button secondary sm:col-span-2" href={orderUrl}>Tomar pedido</Link>{isAdmin && accessUrl && <Link className="button secondary sm:col-span-2" href={accessUrl}>Accesos</Link>}</div>
        </main>
    </>;
}
