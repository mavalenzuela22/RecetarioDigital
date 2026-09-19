import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppShell, Field } from './PurchaseUI';

export type ProductComponent = { concept: string; amount_minor: string; allocation: 'batch' | 'unit' | 'order'; per_unit_micros?: string; denominator?: string };
export type ProductScenario = {
    multiplier: string;
    suggested_price_minor: string;
    profit_per_unit_micros: string;
    expected_yield_profit_micros: string;
    margin_percent: string | null;
};
export type ProductCost = {
    complete: boolean;
    unit_cost_micros: string | null;
    missing: string[];
    current_price_minor: string | null;
    current_price_metrics: { profit_per_unit_micros: string; expected_yield_profit_micros: string; margin_percent: string | null } | null;
    recipe: { batch_cost_micros: string | null; unit_cost_micros: string | null; expected_yield: string | null };
    profile: { id: number; version_number: string; reference_order_quantity: string; components: ProductComponent[] } | null;
    breakdown?: {
        recipe: { batch_cost_micros: string; unit_cost_micros: string; yield: string };
        batch: ProductComponent[];
        unit: ProductComponent[];
        order: ProductComponent[];
    };
    scenarios: ProductScenario[];
};
export type ProductPayload = { id: number; name: string; active: boolean; sale_unit: 'piece'; recipe_id: number; cost: ProductCost };

export function exactDecimal(value: string, scale: number, signed = false): string {
    const negative = signed && value.startsWith('-');
    const raw = negative ? value.slice(1) : value;
    const digits = raw.padStart(scale + 1, '0');
    const whole = digits.slice(0, -scale).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const fraction = digits.slice(-scale);
    return (negative ? '-' : '') + whole + '.' + fraction;
}

export function ProductMoney({ value, scale = 2 }: { value: string | null | undefined; scale?: number }) {
    if (value === null || value === undefined) return <>—</>;
    const negative = value.startsWith('-');
    return <span className="money">{negative ? '-' : ''}{'$'}{exactDecimal(negative ? value.slice(1) : value, scale)} <small>MXN</small></span>;
}

export function ProductCostCard({ cost }: { cost: ProductCost }) {
    if (!cost.complete) {
        return <section className="cost-summary" role="status"><h2>Costo por pieza</h2><p>Falta información para calcular el costo.</p>{cost.missing.length > 0 && <p className="help">Falta registrar: {cost.missing.join(', ')}.</p>}</section>;
    }
    return <section className="cost-summary" aria-label="Costo por pieza"><p className="eyebrow">Costo atribuible actual</p><p className="cost"><ProductMoney value={cost.unit_cost_micros} scale={6} /></p><p>por pieza</p></section>;
}

function AllocationGroup({ title, rows, denominatorLabel }: { title: string; rows: ProductComponent[]; denominatorLabel?: string }) {
    if (!rows.length) return null;
    return <section className="mt-5"><h3 className="font-semibold">{title}</h3>{rows.map((row) => <div key={row.concept + row.allocation} className="mt-3 flex flex-wrap justify-between gap-2 border-b border-ink/10 pb-3 text-sm"><span>{row.concept}<span className="help block">{denominatorLabel ? denominatorLabel + ' (' + row.denominator + ' piezas)' : 'Se aplica por pieza'}</span></span><span><ProductMoney value={row.per_unit_micros ?? '0'} scale={6} /></span></div>)}</section>;
}

export function CostBreakdown({ cost }: { cost: ProductCost }) {
    if (!cost.complete || !cost.breakdown) return null;
    return <details className="mt-6 rounded-2xl border border-ink/10 bg-paper p-4"><summary className="min-h-12 cursor-pointer font-semibold">Ver desglose de costos</summary><div className="mt-3"><p className="help">Receta: <ProductMoney value={cost.breakdown.recipe.unit_cost_micros} scale={6} /> por pieza · tanda de {cost.breakdown.recipe.yield} piezas.</p><AllocationGroup title="Extras por tanda" rows={cost.breakdown.batch} denominatorLabel="Costo dividido entre el rendimiento" /><AllocationGroup title="Extras por pieza" rows={cost.breakdown.unit} /><AllocationGroup title="Extras por pedido" rows={cost.breakdown.order} denominatorLabel="Costo dividido entre la cantidad de referencia" /></div></details>;
}

export function ProductShell({ title, children, back, onBack }: { title: string; children: ReactNode; back?: string; onBack?: () => void }) {
    return <AppShell title={title} back={back} onBack={onBack}>{children}</AppShell>;
}

export function ActiveBadge({ active }: { active: boolean }) {
    return <span className="badge">{active ? 'Activo' : 'Inactivo'}</span>;
}

export function ProductLink({ href, children }: { href: string; children: ReactNode }) {
    return <Link className="ingredient-row" href={href}>{children}</Link>;
}

export { Field };
