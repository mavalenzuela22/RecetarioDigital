import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { AppShell, Money, perUnit, type Ingredient } from '../../Components/PurchaseUI';

export default function Index({ ingredients, createUrl }: { ingredients: Ingredient[]; createUrl: string }) {
    const [search, setSearch] = useState('');
    const results = ingredients.filter((ingredient) => ingredient.name.toLocaleLowerCase('es-MX').includes(search.toLocaleLowerCase('es-MX')));
    return <AppShell title="Ingredientes">
        <p className="intro">Tus compras y el costo más reciente de cada ingrediente, en un solo lugar.</p>
        <Link className="button primary" href={createUrl}>Registrar compra</Link>
        {ingredients.length ? <>
            <div className="field"><label htmlFor="search">Buscar ingrediente</label><input id="search" type="search" value={search} onChange={(event) => setSearch(event.target.value)} /></div>
            <p className="help" role="status">{results.length} ingredientes</p>
            <ul className="ingredient-list">{results.map((ingredient) => <li key={ingredient.id}>
                <Link className="ingredient-row" href={ingredient.url!}>
                    <strong>{ingredient.name}</strong>
                    {ingredient.current_purchase ? <span><Money value={ingredient.current_purchase.normalized_unit_cost_micros} scale={6} /><span className="help"> por {perUnit(ingredient.canonical_unit)}</span></span> : <span>Sin compras registradas</span>}
                    <span className="help">Ver historial →</span>
                </Link>
            </li>)}</ul>
            {!results.length && <section className="empty"><h2>No encontramos ese ingrediente</h2><button type="button" className="button secondary" onClick={() => setSearch('')}>Limpiar búsqueda</button></section>}
        </> : <section className="empty"><h2>Empieza con tu primera compra</h2><p>Cuéntanos qué compraste y cuánto pagaste. Calcularemos el costo por gramo, mililitro o pieza.</p></section>}
    </AppShell>;
}
