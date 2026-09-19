import { Link } from '@inertiajs/react';
import { ActiveBadge, ProductLink, ProductMoney, ProductShell } from '../../Components/ProductUI';

type ProductSummary = { id: number; name: string; active: boolean; complete: boolean; unit_cost_micros: string | null; current_price_minor: string | null; url: string };
type RecipeOption = { id: number; name: string; url: string };

export default function Index({ products, recipes, recipesUrl }: { products: ProductSummary[]; recipes: RecipeOption[]; recipesUrl: string }) {
    return <ProductShell title="Productos" back={recipesUrl}>
        <p className="intro">Conoce tu costo por pieza y explora un precio que tenga sentido.</p>
        {products.length > 0 && <ul className="ingredient-list" aria-label="Productos">{products.map((product) => <li key={product.id}><ProductLink href={product.url}><span className="flex items-center justify-between gap-3"><strong>{product.name}</strong><ActiveBadge active={product.active} /></span><span>{product.complete ? <><ProductMoney value={product.unit_cost_micros} scale={6} /> <span className="help">por pieza{product.current_price_minor ? ' · Precio actual ' : ''}{product.current_price_minor ? <ProductMoney value={product.current_price_minor} /> : null}</span></> : 'Falta información para calcular el costo.'}</span><span className="help">Ver costos y precio →</span></ProductLink></li>)}</ul>}
        <section className="mt-8" aria-labelledby="unconfigured-title"><h2 id="unconfigured-title">Recetas sin producto</h2>{recipes.length > 0 ? <ul className="ingredient-list">{recipes.map((recipe) => <li key={recipe.id}><ProductLink href={recipe.url}><strong>{recipe.name}</strong><span className="help">Configurar producto de esta receta →</span></ProductLink></li>)}</ul> : <p className="help">Todas tus recetas ya tienen un producto.</p>}</section>
        <Link className="button secondary mt-4 w-full" href={recipesUrl}>Volver al Recetario</Link>
    </ProductShell>;
}
