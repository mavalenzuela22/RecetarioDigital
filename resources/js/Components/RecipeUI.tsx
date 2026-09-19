import { Link } from '@inertiajs/react';
import { AppShell, Field, Money, decimal, perUnit, type Ingredient } from './PurchaseUI';

export type RecipeLine = { ingredient_id: string | number; ingredient_name?: string | null; canonical_unit?: string | null; quantity: string; unit: string };
export type RecipeCost = {
    complete: boolean;
    missing: string[];
    batch_cost_micros: string | null;
    unit_cost_micros: string | null;
    lines?: Array<{ ingredient_id: number; snapshot_usage_cost_micros: string | null }>;
};
export type RecipePayload = {
    id: number;
    version_id: number;
    version_number: string;
    name: string;
    expected_yield: string;
    instructions: string;
    notes: string;
    image_url: string | null;
    snapshot_batch_cost_micros: string | null;
    snapshot_unit_cost_micros: string | null;
    lines: RecipeLine[];
    cost?: RecipeCost | null;
};

export const compatibleUnits = (canonical?: string | null) => canonical === 'g' ? ['g', 'kg'] : canonical === 'ml' ? ['ml', 'l'] : ['piece'];
export const unitLabel = (unit: string) => ({ piece: 'pieza', g: 'g', kg: 'kg', ml: 'ml', l: 'l' }[unit] ?? unit);
export const emptyLine = (): RecipeLine => ({ ingredient_id: '', quantity: '', unit: 'g' });

export function RecipeCostDisclosure({ cost }: { cost?: RecipeCost | null }) {
    if (!cost || !cost.complete) {
        return <aside className="cost-summary" role="status"><h2>Costo de esta receta</h2><p>Falta el costo de un ingrediente. No podemos calcular el total.</p>{cost?.missing?.length ? <p className="help">Falta registrar: {cost.missing.join(', ')}.</p> : <p className="help">El costo se confirmará al guardar con las compras vigentes.</p>}</aside>;
    }
    return <aside className="cost-summary" role="region" aria-label="Costo de esta receta"><h2>Costo de esta receta</h2><p className="cost"><Money value={cost.batch_cost_micros ?? '0'} scale={6} /></p><p>Por tanda</p><p><strong><Money value={cost.unit_cost_micros ?? '0'} scale={6} /></strong> por {perUnit('piece')}</p></aside>;
}

export function RecipeImage({ src }: { src: string | null }) {
    return src ? <img src={src} alt="Imagen de la receta" className="mt-3 h-[180px] w-full rounded-2xl object-cover" /> : <div className="mt-3 grid h-[120px] place-items-center rounded-2xl bg-positive-soft text-sm text-ink/60">Sin foto</div>;
}

export function RecipeBack({ href }: { href: string }) {
    return <Link className="back" href={href}>← Volver</Link>;
}

export { AppShell, Field, Money, decimal, perUnit };
export type { Ingredient };
