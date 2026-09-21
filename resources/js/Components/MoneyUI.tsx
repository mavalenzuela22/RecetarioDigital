import type { ReactNode } from 'react';

export type MoneyValue = string | bigint;
export type MoneyUnit = 'minor' | 'micros';
export type MoneyKind = 'ordinary' | 'normalized-unit';

function signedDigits(value: MoneyValue): { negative: boolean; digits: string } {
    const raw = String(value);
    const negative = raw.startsWith('-');
    const digits = negative ? raw.slice(1) : raw;

    if (!/^\d+$/.test(digits)) {
        throw new Error('Money values must be signed integer strings.');
    }

    return { negative, digits: digits.replace(/^0+(?=\d)/, '') };
}

function increment(digits: string): string {
    const result = digits.split('');
    let position = result.length - 1;
    while (position >= 0 && result[position] === '9') {
        result[position] = '0';
        position -= 1;
    }
    if (position < 0) return `1${result.join('')}`;
    result[position] = String.fromCharCode(result[position].charCodeAt(0) + 1);
    return result.join('');
}

function group(digits: string): string {
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function scaledNumber(value: MoneyValue, scale: number, trim: boolean): string {
    const { negative, digits: rawDigits } = signedDigits(value);
    const digits = rawDigits.padStart(scale + 1, '0');
    const whole = group(digits.slice(0, -scale));
    const fraction = digits.slice(-scale).padStart(scale, '0');
    const visibleFraction = trim ? fraction.replace(/0+$/, '') : fraction;
    const number = visibleFraction ? `${whole}.${visibleFraction}` : whole;

    return negative && number !== '0' ? `-${number}` : number;
}

function roundedMicrosToMinor(value: MoneyValue): string {
    const { negative, digits: rawDigits } = signedDigits(value);
    const digits = rawDigits.padStart(5, '0');
    const minor = digits.slice(0, -4).replace(/^0+(?=\d)/, '');
    const remainder = digits.slice(-4);
    const rounded = remainder >= '5000' ? increment(minor) : minor;
    const signed = rounded === '0' ? '' : negative ? '-' : '';

    return `${signed}${rounded}`;
}

function currencyAmount(value: MoneyValue, unit: MoneyUnit, kind: MoneyKind): string {
    const amount = unit === 'micros'
        ? kind === 'normalized-unit'
            ? scaledNumber(value, 6, true)
            : scaledNumber(roundedMicrosToMinor(value), 2, false)
        : scaledNumber(value, 2, false);

    return amount.startsWith('-') ? `-$${amount.slice(1)}` : `$${amount}`;
}

export function formatMoneyAmount(value: MoneyValue, unit: MoneyUnit = 'minor', kind: MoneyKind = 'ordinary'): string {
    return currencyAmount(value, unit, kind);
}

export function formatMoney(value: MoneyValue, unit: MoneyUnit = 'minor', kind: MoneyKind = 'ordinary'): string {
    return `${formatMoneyAmount(value, unit, kind)} MXN`;
}

export function formatMinor(value: MoneyValue): string {
    return formatMoney(value, 'minor', 'ordinary');
}

export function formatOrdinaryMicros(value: MoneyValue): string {
    return formatMoney(value, 'micros', 'ordinary');
}

export function formatNormalizedUnitCost(value: MoneyValue): string {
    return formatMoney(value, 'micros', 'normalized-unit');
}

export function formatMinorAmount(value: MoneyValue): string {
    return scaledNumber(value, 2, false);
}

export function formatManualAmount(value: string): string {
    const normalized = value.replace(',', '.');
    const match = /^(\d+)(?:\.(\d{0,2}))?$/.exec(normalized);
    if (!match) return value;
    return `${group(match[1])}.${(match[2] ?? '').padEnd(2, '0')}`;
}

export function Money({ value, unit = 'minor', kind = 'ordinary' }: { value: MoneyValue | null | undefined; unit?: MoneyUnit; kind?: MoneyKind }): ReactNode {
    if (value === null || value === undefined) return <>—</>;
    return <span className="money">{formatMoneyAmount(value, unit, kind)} <small>MXN</small></span>;
}
