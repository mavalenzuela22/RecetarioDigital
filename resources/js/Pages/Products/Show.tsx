import { Link, useForm } from '@inertiajs/react';
import { useRef, useState, type FormEvent } from 'react';
import { ActiveBadge, CostBreakdown, ProductCostCard, ProductMoney, ProductShell, type ProductPayload, type ProductScenario } from '../../Components/ProductUI';

type Props = { product: ProductPayload; success?: string; indexUrl: string; editUrl: string; scenarioPriceUrl: string; historyUrl: string; scenarioRequestKey: string; manualRequestKey: string };
type PriceData = { mode: 'scenario' | 'manual'; scenario: string; price: string; confirmed: boolean; request_key: string };

export default function Show({ product, success, indexUrl, editUrl, scenarioPriceUrl, historyUrl, scenarioRequestKey, manualRequestKey }: Props) {
    const { cost } = product;
    const [selected, setSelected] = useState<ProductScenario | null>(cost.scenarios[0] ?? null);
    const [manual, setManual] = useState('');
    const [priceMode, setPriceMode] = useState<'scenario' | 'manual'>('scenario');
    const dialog = useRef<HTMLDialogElement>(null);
    const priceForm = useForm<PriceData>({ mode: 'scenario', scenario: selected?.multiplier ?? '2', price: '', confirmed: true, request_key: scenarioRequestKey });

    function choose(scenario: ProductScenario) {
        setSelected(scenario);
        setPriceMode('scenario');
        priceForm.setData({ mode: 'scenario', scenario: scenario.multiplier, price: '', confirmed: true, request_key: scenarioRequestKey });
    }
    function askScenario() {
        if (!selected) return;
        setPriceMode('scenario');
        priceForm.setData({ mode: 'scenario', scenario: selected.multiplier, price: '', confirmed: true, request_key: scenarioRequestKey });
        dialog.current?.showModal();
    }
    function askManual() {
        setPriceMode('manual');
        priceForm.setData({ mode: 'manual', scenario: '', price: manual, confirmed: true, request_key: manualRequestKey });
        dialog.current?.showModal();
    }
    function submitPrice(event: FormEvent) {
        event.preventDefault();
        if (!priceForm.processing) priceForm.post(scenarioPriceUrl, { preserveScroll: true, onSuccess: () => dialog.current?.close() });
    }

    return <ProductShell title="Costos y precio" back={indexUrl}>
        <div className="flex items-start justify-between gap-3"><div><p className="intro mb-2">{product.name} · por pieza</p><ActiveBadge active={product.active} /></div><a className="back" href={editUrl}>Editar</a></div>
        {success && <p className="success" role="status">{success}</p>}
        <section className="mt-6 rounded-2xl border border-ink/10 bg-paper p-4" aria-label="Resumen de precio"><p className="help">Precio actual</p><p className="cost"><ProductMoney value={cost.current_price_minor} /></p>{cost.current_price_minor && cost.current_price_metrics && <dl><div><dt>Ganancia por pieza</dt><dd><ProductMoney value={cost.current_price_metrics.profit_per_unit_micros} scale={6} /></dd></div><div><dt>Margen sobre venta</dt><dd>{cost.current_price_metrics.margin_percent ?? '—'}%</dd></div></dl>}{cost.current_price_metrics?.profit_per_unit_micros.startsWith('-') && <p className="help">Pérdida por pieza</p>}</section>
        <ProductCostCard cost={cost} />
        <CostBreakdown cost={cost} />
        <Link className="button secondary mt-5 w-full" href={historyUrl}>Antes y ahora</Link>
        <section className="mt-8" aria-labelledby="scenario-title"><h2 id="scenario-title">Explora otro precio</h2><p className="help">El multiplicador no es el margen: el margen es la ganancia sobre el precio de venta.</p>{cost.complete ? <><div className="mt-4 grid grid-cols-2 gap-2">{cost.scenarios.map((scenario) => <button key={scenario.multiplier} type="button" className={selected?.multiplier === scenario.multiplier ? 'button primary' : 'button secondary'} aria-pressed={selected?.multiplier === scenario.multiplier} onClick={() => choose(scenario)}>×{scenario.multiplier}</button>)}</div>{selected && <article className="cost-summary mt-4" aria-label="Resultado del escenario"><p className="eyebrow">Escenario ×{selected.multiplier}</p><p className="cost"><ProductMoney value={selected.suggested_price_minor} /></p><p>Precio sugerido</p><dl><div><dt>Ganancia por pieza</dt><dd><ProductMoney value={selected.profit_per_unit_micros} scale={6} /></dd></div><div><dt>Ganancia esperada por tanda</dt><dd><ProductMoney value={selected.expected_yield_profit_micros} scale={6} /></dd></div><div><dt>Margen sobre venta</dt><dd>{selected.margin_percent ?? '—'}%</dd></div></dl>{selected.profit_per_unit_micros.startsWith('-') && <p className="help">Pérdida por pieza</p>}<button type="button" className="button primary mt-5 w-full" onClick={askScenario}>Usar este precio</button></article>}</> : <p className="error-message">Falta información para calcular el costo.</p>}</section>
        <section className="mt-8 rounded-2xl border border-ink/10 bg-paper p-4" aria-labelledby="manual-title"><h2 id="manual-title">Explora otro precio</h2><label className="field"><span>Precio manual (MXN)</span><input inputMode="decimal" value={manual} onChange={(event) => setManual(event.target.value)} placeholder="Ej. 35.00" /></label><button type="button" className="button secondary mt-4 w-full" onClick={askManual} disabled={!manual || priceForm.processing}>Usar precio manual</button>{priceForm.errors.price && <p className="error-text">{priceForm.errors.price}</p>}{priceForm.errors.request_key && <p className="error-text">{priceForm.errors.request_key}</p>}</section>
        <dialog ref={dialog} aria-labelledby="price-confirm-title"><h2 id="price-confirm-title">¿Confirmar nuevo precio?</h2><p>El nuevo precio se aplicará a nuevos pedidos. Los anteriores conservan su precio acordado.</p><p className="font-semibold">{priceMode === 'scenario' ? 'Precio sugerido: ' + (selected ? '$' + ProductMoneyText(selected.suggested_price_minor) : '—') : 'Precio manual: $' + ProductMoneyText(manual)}</p>{priceForm.errors.confirmed && <p className="error-text">{priceForm.errors.confirmed}</p>}<div className="dialog-actions"><button type="button" className="button secondary" onClick={() => dialog.current?.close()}>Seguir explorando</button><button type="button" className="button primary" onClick={submitPrice} disabled={priceForm.processing}>{priceForm.processing ? 'Guardando precio…' : 'Confirmar precio'}</button></div></dialog>
    </ProductShell>;
}

function ProductMoneyText(value: string): string {
    const digits = value.padStart(3, '0');
    return digits.slice(0, -2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '.' + digits.slice(-2);
}
