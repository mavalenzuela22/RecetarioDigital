import { Link } from '@inertiajs/react';
import { AppShell, Money, perUnit } from '../../Components/RecipeUI';

type RecipeSummary = { id: number; name: string; version_number: string | null; unit_cost_micros: string | null; complete: boolean; url: string };

export default function Index({ recipes, createUrl }: { recipes: RecipeSummary[]; createUrl: string }) {
    return <AppShell title="Recetario">
        <p className="intro">Tus recetas, sus versiones y el costo vigente de cada pieza.</p>
        <Link className="button primary" href={createUrl}>Nueva receta</Link>
        {recipes.length ? <ul className="ingredient-list" aria-label="Recetas">{recipes.map((recipe) => <li key={recipe.id}><Link className="ingredient-row" href={recipe.url}><strong>{recipe.name}</strong><span>{recipe.complete && recipe.unit_cost_micros ? <><Money value={recipe.unit_cost_micros} scale={6} /> <span className="help">por {perUnit('piece')}</span></> : <span>Falta costo de un ingrediente.</span>}</span><span className="help">Versión {recipe.version_number} · Ver receta →</span></Link></li>)}</ul> : <section className="empty"><h2>Crea tu primera receta</h2><p>Agrega los ingredientes y el rendimiento de una tanda para conocer su costo.</p><Link className="button secondary" href={createUrl}>Agregar receta</Link></section>}
    </AppShell>;
}
