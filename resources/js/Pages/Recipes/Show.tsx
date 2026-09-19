import { Link } from '@inertiajs/react';
import { AppShell, Money, RecipeImage, type RecipePayload } from '../../Components/RecipeUI';

export default function Show({ recipe, success, editUrl, indexUrl }: { recipe: RecipePayload; success?: string; editUrl: string; indexUrl: string }) {
    const cost = recipe.cost;
    return <AppShell title={recipe.name} back={indexUrl}>
        {success && <p className="success" role="status">{success}</p>}
        <p className="help">Versión {recipe.version_number} · esta versión es inmutable.</p>
        <RecipeImage src={recipe.image_url} />
        <section className="mt-6"><h2>Ingredientes</h2><ul className="ingredient-list">{recipe.lines.map((line) => { const lineCost = cost?.lines?.find((item) => item.ingredient_id === Number(line.ingredient_id))?.snapshot_usage_cost_micros ?? null; return <li key={String(line.ingredient_id)} className="ingredient-row"><strong>{line.ingredient_name}</strong><span>{line.quantity} {line.unit}</span>{lineCost !== null && <span className="help"><Money value={lineCost} scale={6} /></span>}</li>; })}</ul></section>
        <section className="cost-summary" aria-label="Costo vigente"><h2>Costo de esta receta</h2>{cost?.complete ? <><p className="cost"><Money value={cost.batch_cost_micros ?? '0'} scale={6} /></p><p>por tanda de {recipe.expected_yield} piezas</p><p><strong>Costo por pieza: <Money value={cost.unit_cost_micros ?? '0'} scale={6} /></strong></p></> : <><p>Falta el costo de un ingrediente. No podemos calcular el total.</p><p className="help">Falta registrar: {cost?.missing.join(', ')}.</p></>}</section>
        <p className="help">Snapshot de esta versión: {recipe.snapshot_batch_cost_micros !== null ? <><Money value={recipe.snapshot_batch_cost_micros} scale={6} /> por tanda · <Money value={recipe.snapshot_unit_cost_micros ?? '0'} scale={6} /> por pieza</> : 'incompleto porque faltaba el costo de un ingrediente al guardar.'}</p>
        {recipe.instructions && <section className="mt-6"><h2>Preparación</h2><p className="whitespace-pre-wrap">{recipe.instructions}</p></section>}
        {recipe.notes && <section className="mt-6"><h2>Notas</h2><p className="whitespace-pre-wrap">{recipe.notes}</p></section>}
        <Link className="button primary" href={editUrl}>Editar receta</Link>
        <p className="help">Al editar se conservará esta versión y se creará una nueva al guardar.</p>
    </AppShell>;
}
