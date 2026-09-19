import { router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState, type ChangeEvent, type FormEvent } from 'react';
import { AppShell, Field, RecipeCostDisclosure, compatibleUnits, emptyLine, unitLabel, type Ingredient, type RecipeLine, type RecipePayload } from '../../Components/RecipeUI';

type Props = { ingredients: Ingredient[]; recipe: RecipePayload | null; requestKey: string; storeUrl: string; indexUrl: string; backUrl: string };
type FormData = { name: string; expected_yield: string; instructions: string; notes: string; image: File | null; base_version_id: string; request_key: string; ingredients: RecipeLine[] };

export default function Create({ ingredients, recipe, requestKey, storeUrl, indexUrl, backUrl }: Props) {
    const initialLines = recipe?.lines?.map((line) => ({ ...line, ingredient_id: String(line.ingredient_id) })) ?? [];
    const form = useForm<FormData>({
        name: recipe?.name ?? '', expected_yield: recipe?.expected_yield ?? '', instructions: recipe?.instructions ?? '', notes: recipe?.notes ?? '', image: null,
        base_version_id: recipe ? String(recipe.version_id) : '', request_key: requestKey, ingredients: initialLines,
    });
    const [search, setSearch] = useState('');
    const [optional, setOptional] = useState(Boolean(recipe?.instructions || recipe?.notes));
    const discard = useRef<HTMLDialogElement>(null);
    const allowLeave = useRef(false);
    const filteredIngredients = ingredients.filter((ingredient) => ingredient.name.toLocaleLowerCase('es-MX').includes(search.toLocaleLowerCase('es-MX')));
    const error = (key: string) => (form.errors as Record<string, string | undefined>)[key];

    useEffect(() => {
        const beforeUnload = (event: BeforeUnloadEvent) => { if (form.isDirty && !allowLeave.current) { event.preventDefault(); event.returnValue = ''; } };
        window.addEventListener('beforeunload', beforeUnload);
        return () => window.removeEventListener('beforeunload', beforeUnload);
    }, [form.isDirty]);
    useEffect(() => {
        const firstError = Object.keys(form.errors)[0];
        if (!firstError) return;
        const frame = requestAnimationFrame(() => document.getElementById(firstError)?.focus());
        return () => cancelAnimationFrame(frame);
    }, [form.errors]);

    function updateLine(index: number, changes: Partial<RecipeLine>) {
        const next = form.data.ingredients.map((line, lineIndex) => lineIndex === index ? { ...line, ...changes } : line);
        form.setData('ingredients', next);
    }
    function selectIngredient(index: number, event: ChangeEvent<HTMLSelectElement>) {
        const ingredient = ingredients.find((item) => String(item.id) === event.target.value);
        updateLine(index, { ingredient_id: event.target.value, unit: compatibleUnits(ingredient?.canonical_unit)[0] ?? 'g' });
    }
    function back() {
        if (form.processing) return;
        if (form.isDirty) discard.current?.showModal();
        else router.visit(backUrl);
    }
    function submit(event: FormEvent) {
        event.preventDefault();
        if (form.processing) return;
        form.post(storeUrl, { forceFormData: true, preserveScroll: true, onSuccess: () => { allowLeave.current = true; } });
    }
    const input = (key: keyof FormData, hint = false) => ({
        id: key, name: key, value: form.data[key] as string, disabled: form.processing,
        'aria-invalid': Boolean(error(String(key))), 'aria-describedby': [hint ? `${key}-hint` : '', error(String(key)) ? `${key}-error` : ''].filter(Boolean).join(' ') || undefined,
        onChange: (event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => form.setData(key, event.target.value as never),
    });

    return <AppShell title={recipe ? `Receta · versión ${recipe.version_number}` : 'Nueva receta'} onBack={back}>
        <p className="intro">Guarda los ingredientes y el rendimiento. El costo se calcula con las compras vigentes.</p>
        {ingredients.length === 0 && <p className="error-message" role="alert">Primero registra al menos una compra para poder elegir un ingrediente.</p>}
        <form onSubmit={submit} noValidate aria-busy={form.processing}>
            <fieldset disabled={form.processing}>
                <Field id="name" label="Nombre de la receta" hint="Máximo 120 caracteres." error={error('name')}><input {...input('name', true)} maxLength={120} required placeholder="Ej. Roles de canela" /></Field>
                <section className="mt-6" aria-labelledby="ingredients-title">
                    <div className="flex items-end justify-between gap-3"><div><h2 id="ingredients-title">Ingredientes</h2><p className="help">Elige ingredientes que ya registraste.</p></div><span className="help">{form.data.ingredients.length} líneas</span></div>
                    <Field id="ingredient-search" label="Buscar ingrediente"><input id="ingredient-search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Ej. Harina" type="search" /></Field>
                    {error('ingredients') && <p className="error-text" id="ingredients-error">{error('ingredients')}</p>}
                    {form.data.ingredients.map((line, index) => {
                        const ingredient = ingredients.find((item) => String(item.id) === String(line.ingredient_id));
                        const options = filteredIngredients.some((item) => String(item.id) === String(line.ingredient_id)) || !line.ingredient_id ? filteredIngredients : [ingredient!, ...filteredIngredients];
                        const lineError = error(`ingredients.${index}.ingredient_id`) || error(`ingredients.${index}.quantity`) || error(`ingredients.${index}.unit`);
                        return <div key={index} className="mt-4 rounded-2xl border border-ink/10 bg-paper p-3" data-testid="recipe-line">
                            <div className="flex items-center justify-between gap-2"><strong>Ingrediente {index + 1}</strong>{form.data.ingredients.length > 1 && <button type="button" className="min-h-12 px-2 text-sm font-semibold text-primary" onClick={() => form.setData('ingredients', form.data.ingredients.filter((_, lineIndex) => lineIndex !== index))}>Quitar</button>}</div>
                            <label className="field mt-3 block"><span className="mb-2 block font-medium">Ingrediente</span><select id={`ingredients.${index}.ingredient_id`} value={String(line.ingredient_id)} onChange={(event) => selectIngredient(index, event)} aria-invalid={Boolean(error(`ingredients.${index}.ingredient_id`))} required><option value="">Elige un ingrediente</option>{options.filter(Boolean).map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></label>
                            <div className="quantity-row mt-3"><Field id={`ingredients.${index}.quantity`} label="Cantidad" hint="Hasta 3 decimales." error={error(`ingredients.${index}.quantity`)}><input id={`ingredients.${index}.quantity`} inputMode="decimal" value={line.quantity} onChange={(event) => updateLine(index, { quantity: event.target.value })} placeholder="320" required /></Field><Field id={`ingredients.${index}.unit`} label="Unidad" error={error(`ingredients.${index}.unit`)}><select id={`ingredients.${index}.unit`} value={line.unit} onChange={(event) => updateLine(index, { unit: event.target.value })} required>{compatibleUnits(ingredient?.canonical_unit).map((unit) => <option key={unit} value={unit}>{unitLabel(unit)}</option>)}</select></Field></div>
                            {lineError && <p className="error-text" role="alert">{lineError}</p>}
                        </div>;
                    })}
                    <button type="button" className="button secondary mt-4 w-full" onClick={() => form.setData('ingredients', [...form.data.ingredients, emptyLine()])} disabled={ingredients.length === 0}>+ Agregar ingrediente</button>
                </section>
                <Field id="expected_yield" label="Rendimiento esperado" hint="¿Cuántas piezas produce esta tanda? Usa un número entero mayor que cero." error={error('expected_yield')}><input {...input('expected_yield', true)} inputMode="numeric" pattern="[0-9]*" required placeholder="12" /></Field>
                <button type="button" className="optional-toggle" aria-expanded={optional} aria-controls="recipe-details" onClick={() => setOptional(!optional)}>{optional ? '− Ocultar preparación y notas' : '+ Agregar preparación y notas'} <span className="help">(opcional)</span></button>
                <div id="recipe-details" hidden={!optional}>
                    <Field id="instructions" label="Preparación" hint={`${form.data.instructions.length}/10000 caracteres.`} error={error('instructions')}><textarea {...input('instructions', true)} maxLength={10000} rows={5} /></Field>
                    <Field id="notes" label="Notas" hint={`${form.data.notes.length}/2000 caracteres.`} error={error('notes')}><textarea {...input('notes', true)} maxLength={2000} rows={3} /></Field>
                </div>
                <Field id="image" label="Foto (opcional)" hint="JPEG, PNG o WebP. Máximo 5 MiB." error={error('image')}><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => form.setData('image', event.target.files?.[0] ?? null)} /></Field>
            </fieldset>
            <RecipeCostDisclosure cost={recipe?.cost} />
            <p className="help">Cada guardado crea una nueva versión. La versión anterior conserva su costo histórico.</p>
            {error('base_version_id') && <p className="error-message" role="alert">{error('base_version_id')}</p>}
            {error('request_key') && <p className="error-message" role="alert">{error('request_key')}</p>}
            {!!Object.keys(form.errors).length && <p role="alert" className="error-text">Revisa los campos señalados. Tus datos siguen aquí.</p>}
            <div className="sticky-actions"><button className="button primary" type="submit" disabled={form.processing}>{form.processing ? 'Guardando receta…' : 'Guardar receta'}</button>{form.processing && <p className="help" role="status">Espera mientras confirmamos la nueva versión.</p>}</div>
        </form>
        <dialog ref={discard} aria-labelledby="discard-recipe-title"><h2 id="discard-recipe-title">¿Descartar esta receta?</h2><p>Los cambios que escribiste todavía no se han guardado.</p><div className="dialog-actions"><button type="button" className="button secondary" autoFocus onClick={() => discard.current?.close()}>Seguir editando</button><button type="button" className="button danger" onClick={() => { allowLeave.current = true; discard.current?.close(); router.visit(indexUrl); }}>Descartar cambios</button></div></dialog>
    </AppShell>;
}
