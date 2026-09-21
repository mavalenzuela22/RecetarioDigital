import { router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { ProductMoney, ProductShell, type HistoryComparison, type HistorySide } from '../../Components/ProductUI';

type Props = { product: { id: number; name: string }; asOf: string; until: string; comparison: HistoryComparison | null; productUrl: string; pricingUrl: string };

export default function History({ product, asOf: initialAsOf, until: initialUntil, comparison, productUrl, pricingUrl }: Props) {
    const [asOf, setAsOf] = useState(initialAsOf);
    const [until, setUntil] = useState(initialUntil);

    function compare(event: FormEvent) {
        event.preventDefault();
        router.get(window.location.pathname, { asOf, until }, { preserveState: true, preserveScroll: true });
    }

    return <ProductShell title="Antes y ahora" back={productUrl}>
        <p className="intro">{product.name}</p>
        <form onSubmit={compare} aria-label="Comparar fechas">
            <div className="date-pair">
                <label className="field"><span>Antes</span><input aria-label="Fecha inicial" type="date" value={asOf} onChange={(event) => setAsOf(event.target.value)} required /></label>
                <label className="field"><span>Ahora</span><input aria-label="Fecha final" type="date" value={until} onChange={(event) => setUntil(event.target.value)} required /></label>
            </div>
            <button className="button primary mt-5 w-full" type="submit">Comparar fechas</button>
        </form>
        <p className="history-disclaimer">Compara la economía configurada del producto. No recalcula pedidos históricos.</p>
        {comparison?.error && <p className="error-message" role="alert">{comparison.error}</p>}
        {comparison && !comparison.error && <ComparisonView comparison={comparison} />}
        <a className="button secondary mt-6 w-full" href={pricingUrl}>Explorar otro precio</a>
    </ProductShell>;
}

function ComparisonView({ comparison }: { comparison: HistoryComparison }) {
    if (!comparison.before || !comparison.now) return null;
    const bothAvailable = comparison.before.available && comparison.now.available;

    return <div className="history-results">
        {!bothAvailable && <p className="error-message" role="status">{comparison.before.message ?? comparison.now.message}</p>}
        <div className="history-sides"><HistorySideCard title="Antes" side={comparison.before} /><HistorySideCard title="Ahora" side={comparison.now} /></div>
        {bothAvailable && comparison.before.complete && comparison.now.complete && <section className="cost-summary" aria-label="Variación de costo"><p className="eyebrow">Cambio en costo por pieza</p><p className="cost"><ProductMoney value={comparison.cost_delta_micros} unit="micros" /></p><p>{comparison.cost_delta_percent === null ? 'Porcentaje no calculable porque el costo anterior es $0.' : comparison.cost_delta_percent + '% frente al costo anterior'}</p></section>}
        {bothAvailable && comparison.recipe_changed && <p className="notice" role="status">La receta o el rendimiento cambiaron. Compara el desglose de cada versión.</p>}
        {bothAvailable && comparison.profile_changed && <p className="notice" role="status">Los costos adicionales configurados cambiaron.</p>}
        {bothAvailable && comparison.drivers && <Drivers drivers={comparison.drivers} />}
        <Provenance before={comparison.before} now={comparison.now} />
    </div>;
}

function HistorySideCard({ title, side }: { title: string; side: HistorySide }) {
    if (!side.available) return <article className="history-side" aria-label={title}><h2>{title}</h2><p className="help">{side.date}</p><p className="error-message" role="status">{side.message}</p></article>;
    return <article className="history-side" aria-label={title}><h2>{title}</h2><p className="help">{side.date}</p>{!side.complete && <p className="pending">Costo pendiente: falta una compra efectiva para una línea de receta.</p>}<dl><div><dt>Costo por pieza</dt><dd><ProductMoney value={side.economics.unit_cost_micros} unit="micros" /></dd></div><div><dt>Precio de venta</dt><dd><ProductMoney value={side.economics.sale_price_minor} /></dd></div><div><dt>Ganancia por pieza</dt><dd><ProductMoney value={side.economics.profit_per_unit_micros} unit="micros" /></dd></div><div><dt>Margen sobre venta</dt><dd>{side.economics.margin_percent === null ? '—' : side.economics.margin_percent + '%'}</dd></div></dl></article>;
}

function Drivers({ drivers }: { drivers: NonNullable<HistoryComparison['drivers']> }) {
    return <section className="history-section" aria-labelledby="drivers-title"><h2 id="drivers-title">Qué cambió el costo</h2>{drivers.length === 0 ? <p className="help">No hubo cambios en el costo de ingredientes.</p> : <ul className="history-list">{drivers.map((driver) => <li className="history-driver" key={driver.ingredient_id}><span><strong>{driver.ingredient_name}</strong><small>{driver.before_purchase_date ?? 'Sin compra'} → {driver.now_purchase_date ?? 'Sin compra'}</small></span><ProductMoney value={driver.delta_micros} unit="micros" /></li>)}</ul>}</section>;
}

function Provenance({ before, now }: { before: HistorySide; now: HistorySide }) {
    return <details className="history-provenance"><summary>Ver de dónde salen estos datos</summary><div className="history-provenance-grid"><ProvenanceSide title="Antes" side={before} /><ProvenanceSide title="Ahora" side={now} /></div></details>;
}

function ProvenanceSide({ title, side }: { title: string; side: HistorySide }) {
    return <section><h3>{title}</h3>{side.provenance ? <><p>{side.provenance.recipe}</p><p>{side.provenance.profile}</p><p>{side.provenance.price}</p><ul>{side.provenance.ingredients.map((line, index) => <li key={(line.ingredient_name ?? 'ingrediente') + index}>{line.ingredient_name ?? 'Ingrediente'}: {line.purchase_date ?? 'sin compra efectiva'}</li>)}</ul></> : <p>{side.message}</p>}</section>;
}
