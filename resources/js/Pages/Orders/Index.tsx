import { Link } from '@inertiajs/react';
import { dateLabel } from '../../Components/PurchaseUI';
import { FulfillmentStatus, OrderLink, OrderMoney, OrderShell, PaymentStatus } from '../../Components/OrderUI';

type OrderSummary = {
    id: number;
    customer_name: string;
    delivery_date: string;
    delivery_time: string;
    line_count: number;
    total_minor: string;
    balance_minor: string;
    fulfillment_state: string;
    payment_state: string;
    url: string;
};

export default function Index({ orders, createUrl }: { orders: OrderSummary[]; createUrl: string }) {
    return <OrderShell title="Pedidos">
        <p className="intro">Toma pedidos claros y conserva el precio acordado de cada venta.</p>
        <Link className="button primary" href={createUrl}>Tomar pedido</Link>
        {orders.length ? <ul className="ingredient-list" aria-label="Pedidos">{orders.map((order) => <li key={order.id}>
            <OrderLink href={order.url}>
                <span className="flex flex-wrap items-center justify-between gap-2"><strong>{order.customer_name}</strong><span className="help">{dateLabel(order.delivery_date)} · {order.delivery_time}</span></span>
                <span className="flex flex-wrap items-center gap-2"><OrderMoney value={order.total_minor} /><span className="help">{order.line_count} {order.line_count === 1 ? 'producto' : 'productos'}</span></span>
                <span className="flex flex-wrap gap-2"><FulfillmentStatus state={order.fulfillment_state} /><PaymentStatus state={order.payment_state} /></span>
            </OrderLink>
        </li>)}</ul> : <section className="empty"><h2>Aún no hay pedidos</h2><p>Cuando alguien te encargue algo, podrás guardar el precio acordado, el anticipo y el saldo pendiente.</p></section>}
    </OrderShell>;
}
