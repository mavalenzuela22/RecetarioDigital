/** Design handoff types. Not a production API or persistence implementation. */
export type DecimalString = string;
export type Money = { amount: DecimalString; currency: 'MXN' };
export type Completeness = { kind: 'complete' } | { kind: 'incomplete'; missing: string[] };
export type Fulfillment = 'new' | 'confirmed' | 'in_preparation' | 'ready' | 'delivered' | 'cancelled';
export type Payment = 'pending' | 'partial' | 'paid';
export type Allocation = 'batch' | 'unit' | 'order';
export interface CostSummaryProps {
  unitCost: Money | null;
  salePrice: Money;
  unitProfit: Money | null;
  marginPercent: DecimalString | null;
  completeness: Completeness;
  basis: { label: string; effectiveLocalDate: string; referenceOrderQuantity?: DecimalString };
}
export interface PurchaseDraft {
  ingredientId?: string;
  ingredientName: string;
  presentation: string;
  totalQuantity: DecimalString;
  unit: 'g' | 'kg' | 'ml' | 'l' | 'piece';
  totalPaid: Money;
  localDate: string;
  supplier?: string;
  note?: string;
}
export interface OrderRowProps {
  id: string;
  customerName: string;
  deliveryLocalDate: string;
  deliveryLocalTime: string;
  lineSummary: string;
  balance: Money;
  fulfillment: Fulfillment;
  payment: Payment;
  href: string;
}
export interface FormResult {
  kind: 'saved' | 'invalid' | 'network_error' | 'conflict';
  fieldErrors?: Record<string, string>;
  message: string;
}
