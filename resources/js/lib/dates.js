const MONTHS = ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr'];
const WEEKDAYS = ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];

// "2026-11-04" -> Date (mahalliy vaqt zonasiga bog'lanmasdan)
export function parseDate(iso) {
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(Date.UTC(y, m - 1, d));
}

export function addDays(iso, days) {
    const date = parseDate(iso);
    date.setUTCDate(date.getUTCDate() + days);
    return date.toISOString().slice(0, 10);
}

export function formatLong(iso) {
    const d = parseDate(iso);
    return `${WEEKDAYS[d.getUTCDay()]}, ${d.getUTCDate()}-${MONTHS[d.getUTCMonth()]}`;
}

export function formatShort(iso) {
    const d = parseDate(iso);
    return `${d.getUTCDate()}-${MONTHS[d.getUTCMonth()]}`;
}
