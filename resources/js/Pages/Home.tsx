import { Head, Link } from '@inertiajs/react';

const focusAreas = [
    {
        number: '01',
        title: 'Costos claros',
        description: 'Entiende cuánto te cuesta preparar cada producto.',
    },
    {
        number: '02',
        title: 'Precios con sentido',
        description: 'Decide qué cobrar y qué ganas con cada venta.',
    },
    {
        number: '03',
        title: 'Un día más ligero',
        description: 'Ten a la mano lo que necesitas preparar, entregar y cobrar.',
    },
];

export default function Home() {
    return (
        <>
            <Head title="Inicio" />
            <div className="min-h-screen bg-canvas text-ink">
                <header className="mx-auto flex w-full max-w-6xl items-center justify-between px-5 py-5 sm:px-8 lg:px-12">
                    <Link href="/" className="flex min-h-12 items-center gap-3" aria-label="EmprendimientoOS, inicio">
                        <span className="grid size-10 place-items-center rounded-2xl bg-ink text-sm font-bold tracking-[0.18em] text-canvas" aria-hidden="true">
                            EO
                        </span>
                        <span className="text-sm font-semibold tracking-[0.14em] text-ink/80">EMPRENDIMIENTOOS</span>
                    </Link>
                    <span className="hidden rounded-full border border-ink/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-ink/55 sm:inline-flex">
                        Tu operación, en calma
                    </span>
                </header>

                <main>
                    <section className="mx-auto grid max-w-6xl gap-12 px-5 pb-16 pt-10 sm:px-8 sm:pt-16 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:gap-20 lg:px-12 lg:pb-24 lg:pt-20">
                        <div>
                            <p className="mb-6 flex items-center gap-3 text-xs font-bold uppercase tracking-[0.24em] text-primary">
                                <span className="h-px w-8 bg-primary" aria-hidden="true" />
                                Hecho para tu negocio
                            </p>
                            <h1 className="max-w-xl font-display text-5xl leading-[0.98] tracking-[-0.045em] text-ink sm:text-6xl lg:text-7xl">
                                Más claridad para hacer crecer lo que haces bien.
                            </h1>
                            <p className="mt-7 max-w-lg text-lg leading-8 text-ink/65 sm:text-xl">
                                EmprendimientoOS reúne tus costos, precios y pendientes para que puedas tomar decisiones con confianza, incluso en los días más ocupados.
                            </p>
                            <div className="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center">
                                <a href="#primeros-pasos" className="button secondary">
                                    Conoce el punto de partida
                                </a>
                                <Link href="/compras/nueva" className="button primary">Registrar compra</Link>
                                <Link href="/recetas" className="button secondary">Ver recetario</Link>
                            </div>
                        </div>

                        <div className="relative mx-auto w-full max-w-md lg:max-w-none" aria-label="Resumen ilustrativo de una jornada">
                            <div className="absolute -inset-5 rounded-[2.5rem] bg-positive-soft/25 blur-2xl" aria-hidden="true" />
                            <div className="relative overflow-hidden rounded-[2rem] border border-ink/10 bg-paper p-5 shadow-[var(--eo-shadow-raised)] sm:p-7">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className="text-xs font-bold uppercase tracking-[0.2em] text-ink/45">Tu día</p>
                                        <p className="mt-2 font-display text-3xl text-ink">A tu ritmo.</p>
                                    </div>
                                    <span className="rounded-full bg-positive-soft/35 px-3 py-1.5 text-xs font-bold text-ink/65">Hoy</span>
                                </div>
                                <div className="mt-8 rounded-2xl bg-canvas p-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-semibold text-ink/70">Un vistazo, sin hojas sueltas</span>
                                        <span className="text-lg text-primary" aria-hidden="true">✦</span>
                                    </div>
                                    <div className="mt-5 grid grid-cols-3 gap-2">
                                        <div className="rounded-xl bg-paper p-3"><p className="text-2xl font-semibold text-ink">—</p><p className="mt-1 text-[0.68rem] leading-4 text-ink/50">Por preparar</p></div>
                                        <div className="rounded-xl bg-paper p-3"><p className="text-2xl font-semibold text-ink">—</p><p className="mt-1 text-[0.68rem] leading-4 text-ink/50">Por entregar</p></div>
                                        <div className="rounded-xl bg-paper p-3"><p className="text-2xl font-semibold text-ink">—</p><p className="mt-1 text-[0.68rem] leading-4 text-ink/50">Por cobrar</p></div>
                                    </div>
                                </div>
                                <div className="mt-4 flex items-center gap-3 rounded-2xl border border-dashed border-ink/15 p-4">
                                    <span className="grid size-9 shrink-0 place-items-center rounded-xl bg-primary/12 text-primary" aria-hidden="true">＋</span>
                                    <div><p className="text-sm font-semibold text-ink/75">Tu siguiente paso</p><p className="mt-0.5 text-xs text-ink/50">Registra una compra o una receta.</p></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="primeros-pasos" className="border-y border-ink/10 bg-paper/60">
                        <div className="mx-auto max-w-6xl px-5 py-14 sm:px-8 sm:py-20 lg:px-12">
                            <div className="max-w-xl">
                                <p className="text-xs font-bold uppercase tracking-[0.24em] text-primary">El punto de partida</p>
                                <h2 className="mt-4 font-display text-3xl leading-tight tracking-[-0.03em] text-ink sm:text-4xl">Lo importante, en el momento en que lo necesitas.</h2>
                            </div>
                            <div className="mt-10 grid gap-3 md:grid-cols-3">
                                {focusAreas.map((area) => (
                                    <article key={area.number} className="rounded-3xl border border-ink/10 bg-canvas p-6 sm:p-7">
                                        <p className="text-xs font-bold tracking-[0.18em] text-primary">{area.number}</p>
                                        <h3 className="mt-10 text-lg font-bold text-ink">{area.title}</h3>
                                        <p className="mt-2 text-sm leading-6 text-ink/60">{area.description}</p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="mx-auto flex max-w-6xl flex-col gap-3 px-5 py-8 text-xs text-ink/45 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
                    <span>EmprendimientoOS</span>
                    <span>Una base sencilla para decisiones reales.</span>
                </footer>
            </div>
        </>
    );
}
