import { Link } from '@inertiajs/react';
import { AppShell, CurrentCost, Money, dateLabel, decimal, perUnit, unitName, type Ingredient, type Purchase } from '../../Components/PurchaseUI';

type Props = { ingredient: Ingredient; purchases: { data: Purchase[]; prev_page_url: string | null; next_page_url: string | null; current_page: number; last_page: number }; success?: string; createUrl: string; indexUrl: string };
export default function Show({ ingredient, purchases, success, createUrl, indexUrl }: Props) {
    return <AppShell title={ingredient.name} back={indexUrl}>
        {success && <p className="success" role="status">{success}</p>}
        <CurrentCost ingredient={ingredient} />
        <p className="help">Usamos la fecha de compra más reciente. Si coincide, usamos la última registrada. Las compras anteriores conservan su costo.</p>
        <Link className="button primary" href={createUrl}>Registrar otra compra</Link>
        <section className="history"><h2>Historial de compras</h2><p className="help">De la fecha más reciente a la más antigua.</p>
            <ol className="history-list">{purchases.data.map((purchase) => <li key={purchase.id} className="purchase-card">
                <div className="purchase-heading"><h3><time dateTime={purchase.purchased_on}>{dateLabel(purchase.purchased_on)}</time></h3>{purchase.id === ingredient.current_purchase?.id && <span className="badge">Costo vigente</span>}</div>
                <p className="presentation">{purchase.presentation}</p>
                <dl><div><dt>Cantidad comprada</dt><dd>{decimal(purchase.purchase_quantity_milli, 3, true)} {unitName(purchase.purchase_unit)}</dd></div>
                    <div><dt>Total pagado</dt><dd><Money value={purchase.total_paid_minor} /></dd></div>
                    <div><dt>Equivale a</dt><dd>{decimal(purchase.normalized_quantity_milli, 3, true)} {unitName(ingredient.canonical_unit)}</dd></div>
                    <div><dt>Costo por {perUnit(ingredient.canonical_unit)}</dt><dd><Money value={purchase.normalized_unit_cost_micros} scale={6} /></dd></div>
                </dl>
                {purchase.store && <p className="metadata"><strong>Tienda o proveedor: </strong>{purchase.store}</p>}
                {purchase.note && <p className="metadata"><strong>Nota: </strong>{purchase.note}</p>}
            </li>)}</ol>
            {!purchases.data.length && <p>Aún no hay compras registradas.</p>}
            {purchases.last_page > 1 && <nav className="pagination" aria-label="Páginas del historial">
                {purchases.prev_page_url && <Link className="button secondary" href={purchases.prev_page_url}>Más recientes</Link>}
                <span>Página {purchases.current_page} de {purchases.last_page}</span>
                {purchases.next_page_url && <Link className="button secondary" href={purchases.next_page_url}>Más antiguas</Link>}
            </nav>}
        </section>
    </AppShell>;
}
