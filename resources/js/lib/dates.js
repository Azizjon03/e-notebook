import { t, tk } from './i18n';

const MONTHS = [tk('yanvar'), tk('fevral'), tk('mart'), tk('aprel'), tk('may'), tk('iyun'), tk('iyul'), tk('avgust'), tk('sentabr'), tk('oktabr'), tk('noyabr'), tk('dekabr')];
const WEEKDAYS = [tk('Yakshanba'), tk('Dushanba'), tk('Seshanba'), tk('Chorshanba'), tk('Payshanba'), tk('Juma'), tk('Shanba')];

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
    return t(':weekday, :day-:month', { weekday: t(WEEKDAYS[d.getUTCDay()]), day: d.getUTCDate(), month: t(MONTHS[d.getUTCMonth()]) });
}

export function formatShort(iso) {
    const d = parseDate(iso);
    return t(':day-:month', { day: d.getUTCDate(), month: t(MONTHS[d.getUTCMonth()]) });
}

export function monthTitle(iso) {
    const d = parseDate(iso);
    const name = t(MONTHS[d.getUTCMonth()]);
    return `${name[0].toUpperCase()}${name.slice(1)} ${d.getUTCFullYear()}`;
}

export function addMonths(iso, months) {
    const d = parseDate(iso);
    d.setUTCDate(1);
    d.setUTCMonth(d.getUTCMonth() + months);
    return d.toISOString().slice(0, 10);
}

export function weekdayShort(iso) {
    return t(['Ya', 'Du', 'Se', 'Cho', 'Pa', 'Ju', 'Sha'][parseDate(iso).getUTCDay()]);
}
