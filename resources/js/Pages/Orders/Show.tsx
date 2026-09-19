import { dateLabel } from '../../Components/PurchaseUI';
import { FulfillmentStatus, OrderMoney, OrderShell, PaymentStatus } from '../../Components/OrderUI';

type Line = { id: number; product_name: string; quantity: string; sale_unit: string; agreed_unit_price_minor: string; line_revenue_minor: string; attributable_unit_cost_micros: string | null; attributable_line_cost_micros: string | null; cost_complete: boolean };
type Order = { id: number; customer_name: string; delivery_date: string; delivery_time: string; notes: string | null; fulfillment_state: string; payment_state: string; total_minor: string; paid_minor: string; balance_minor: string; lines: Line[]; profit: { complete: boolean; total_cost_micros: string | null; profit_micros: string | null } };

export default function Show({ order, success, indexUrl }: { order: Order; success?: string; indexUrl: string }) {
    return <OrderShell title="Pedido" back={indexUrl}>
        {success && <p className="success" role="status">{success}</p>}
        <div className="flex flex-wrap gap-2"><FulfillmentStatus state={order.fulfillment_state} /><PaymentStatus state={order.payment_state} /></div>
        <section className="mt-6 rounded-2xl border border-ink/10 bg-paper p-4"><p className="help">Cliente</p><h2>{order.customer_name}</h2><p>{dateLabel(order.delivery_date)} · {order.delivery_time}</p>{order.notes && <p className="metadata">{order.notes}</p>}</section>
        <section className="mt-8" aria-labelledby="lines-title"><h2 id="lines-title">Productos</h2><ul className="ingredient-list">{order.lines.map((line) => <li key={line.id} className="py-4"><div className="flex flex-wrap items-start justify-between gap-2"><div><strong>{line.product_name}</strong><p className="help">{line.quantity} {line.sale_unit === 'piece' ? 'piezas' : line.sale_unit} · Precio acordado <OrderMoney value={line.agreed_unit_price_minor} /></p></div><strong><OrderMoney value={line.line_revenue_minor} /></strong></div></li>)}</ul></section>
        <section className="cost-summary" aria-label="Resumen del pedido"><dl><div><dt>Total del pedido</dt><dd><OrderMoney value={order.total_minor} /></dd></div><div><dt>Anticipo</dt><dd><OrderMoney value={order.paid_minor} /></dd></div><div><dt>Saldo pendiente</dt><dd><OrderMoney value={order.balance_minor} /></dd></div></dl></section>
        <section className="mt-8 rounded-2xl border border-ink/10 bg-paper p-4" aria-label="Ganancia histórica"><h2>Economía del pedido</h2>{order.profit.complete ? <dl><div><dt>Costo atribuido</dt><dd><OrderMoney value={order.profit.total_cost_micros} scale={6} /></dd></div><div><dt>Ganancia estimada</dt><dd><OrderMoney value={order.profit.profit_micros} scale={6} /></dd></div></dl> : <p>Ganancia pendiente de calcular.</p>}</section>
    </OrderShell>;
}
