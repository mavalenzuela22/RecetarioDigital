import { Head, Link, router } from '@inertiajs/react';
import { type ReactNode } from 'react';
import { Money } from './MoneyUI';

export { Money } from './MoneyUI';

export type Purchase = {
    id: number; total_paid_minor: string; purchase_quantity_milli: string;
    normalized_quantity_milli: string; normalized_unit_cost_micros: string;
    purchase_unit: string; presentation: string; purchased_on: string;
    store: string | null; note: string | null;
};
export type Ingredient = { id: number; name: string; canonical_unit: string; current_purchase: Purchase | null; url?: string };
export const unitName = (unit: string) => ({ piece: 'pieza', g: 'g', kg: 'kg', ml: 'ml', l: 'l' }[unit] ?? unit);
export const perUnit = (unit: string) => ({ piece: 'pieza', g: 'gramo', ml: 'mililitro' }[unit] ?? unit);
export function decimal(value: string, scale: number, trim = false): string {
    const digits = value.padStart(scale + 1, '0');
    const whole = digits.slice(0, -scale).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const fraction = trim ? digits.slice(-scale).replace(/0+$/, '') : digits.slice(-scale);
    return fraction ? `${whole}.${fraction}` : whole;
}
export function dateLabel(date: string): string {
    const [year, month, day] = date.split('-');
    return `${day} ${['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'][Number(month) - 1]} ${year}`;
}
export function AppShell({ title, children, back, onBack }: { title: string; children: ReactNode; back?: string; onBack?: () => void }) {
    return <><Head title={title} /><main className="flow">
        <header className="flow-header">
            {onBack ? <button className="back" type="button" onClick={onBack}>← Volver</button> : <Link className="back" href={back ?? '/'}>← Volver</Link>}
            <div className="flex items-center gap-3"><span className="brand">EmprendimientoOS</span><button className="text-sm font-semibold text-primary" type="button" aria-label="Cerrar sesión" onClick={() => router.post('/logout')}>Salir</button></div>
        </header>
        <h1>{title}</h1>{children}
    </main></>;
}
export function Field({ id, label, error, hint, children }: { id: string; label: string; error?: string; hint?: string; children: ReactNode }) {
    return <div className="field"><label htmlFor={id}>{label}</label>{children}
        {hint && <p className="help" id={`${id}-hint`}>{hint}</p>}
        {error && <p className="error-text" id={`${id}-error`}>{error}</p>}
    </div>;
}
export function CurrentCost({ ingredient }: { ingredient: Ingredient }) {
    const purchase = ingredient.current_purchase;
    return <section className="cost-summary" aria-label="Costo vigente">
        <p className="eyebrow">Costo vigente por {perUnit(ingredient.canonical_unit)}</p>
        {purchase ? <><p className="cost"><Money value={purchase.normalized_unit_cost_micros} unit="micros" kind="normalized-unit" /></p>
            <p className="help">Compra del {dateLabel(purchase.purchased_on)}</p>
            {purchase.normalized_unit_cost_micros === '0' && <p className="help">Menor a $0.000001 MXN por {perUnit(ingredient.canonical_unit)}.</p>}
        </> : <p>— Aún no hay compras registradas.</p>}
    </section>;
}
