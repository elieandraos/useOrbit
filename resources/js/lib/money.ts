const amountFormatter = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/**
 * Format an amount with its currency code and two decimals, e.g. "USD 1,500.00". Amounts are never converted.
 */
export function formatMoney(
    amount: string | number,
    currencyCode: string,
): string {
    return `${currencyCode} ${amountFormatter.format(Number(amount))}`;
}
