import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppShell, Money } from './PurchaseUI';

export type OrderProduct = {
    id: number;
    name: string;
    sale_unit: 'piece';
    current_price_minor: string | null;
    cost_complete: boolean;
};

export type OrderLineInput = { product_id: number; quantity: string; agreed_price: string };

export function minorFromDecimal(value: string): bigint | null {
    if (!/^[0-9]+(?:[.,][0-9]{1,2})?$/.test(value)) return null;
    const [whole, fraction = ''] = value.replace(',', '.').split('.');
    return BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0'));
}

export function decimalInputFromMinor(value: string): string {
    const minor = BigInt(value);
    const whole = minor / 100n;
    const fraction = (minor % 100n).toString().padStart(2, '0');
    return fraction === '00' ? whole.toString() : `${whole}.${fraction}`;
}

export function formatMinor(value: bigint): string {
    const whole = value / 100n;
    const fraction = (value % 100n).toString().padStart(2, '0');
    return `$${whole.toLocaleString('es-MX')}.${fraction} MXN`;
}

export function lineRevenue(line: OrderLineInput): bigint | null {
    if (!/^[1-9][0-9]{0,8}$/.test(line.quantity)) return null;
    const price = minorFromDecimal(line.agreed_price);
    return price === null ? null : price * BigInt(line.quantity);
}

export function orderTotal(lines: OrderLineInput[]): bigint {
    return lines.reduce((total, line) => total + (lineRevenue(line) ?? 0n), 0n);
}

export function OrderShell({ title, children, back }: { title: string; children: ReactNode; back?: string }) {
    return <AppShell title={title} back={back}>{children}</AppShell>;
}

export function OrderMoney({ value, scale = 2 }: { value: string | null | undefined; scale?: number }) {
    if (value === null || value === undefined) return <>—</>;
    const negative = value.startsWith('-');
    return <>{negative ? '-' : ''}<Money value={negative ? value.slice(1) : value} scale={scale} /></>;
}

export function PaymentStatus({ state }: { state: string }) {
    return <span className="badge">{{ pending: 'Pendiente de cobro', partial: 'Pago parcial', paid: 'Pagado' }[state] ?? state}</span>;
}

export function FulfillmentStatus({ state }: { state: string }) {
    return <span className="badge">{{ confirmed: 'Confirmado' }[state] ?? state}</span>;
}

export function OrderLink({ href, children }: { href: string; children: ReactNode }) {
    return <Link className="ingredient-row" href={href}>{children}</Link>;
}
