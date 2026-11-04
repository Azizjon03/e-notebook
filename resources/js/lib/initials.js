/** "Ali Valiyev" -> "AV". Avatar o'rniga ishlatiladi. */
export function initials(first, last) {
    return `${(first ?? '').trim()[0] ?? ''}${(last ?? '').trim()[0] ?? ''}`.toUpperCase() || 'e';
}
