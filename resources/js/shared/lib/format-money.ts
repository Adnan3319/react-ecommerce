const CURRENCY = 'BDT';
const LOCALE = 'en-BD';
const MINOR_UNITS_PER_MAJOR = 100;

const formatter = new Intl.NumberFormat(LOCALE, {
    style: 'currency',
    currency: CURRENCY,
    currencyDisplay: 'narrowSymbol',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
});

/** Formats an integer amount in minor units (poisha) for display. */
export function formatMoney(minorUnits: number): string {
    return formatter.format(minorUnits / MINOR_UNITS_PER_MAJOR);
}
