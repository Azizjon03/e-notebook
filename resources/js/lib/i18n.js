// Tarjima: kalit — o'zbekcha matnning o'zi. ru/en lug'atlari serverdan keladi (lang/*.json).
let dict = {};
let current = 'uz';

export function setI18n(props) {
    current = props?.locale || 'uz';
    dict = props?.translations || {};
}

export function locale() {
    return current;
}

export function t(key, replace = {}) {
    let text = dict[key] ?? key;
    // Uzun nomlar avval: ":days" ":day" dan oldin almashtiriladi.
    const entries = Object.entries(replace).sort((a, b) => b[0].length - a[0].length);
    for (const [name, value] of entries) {
        text = text.replaceAll(`:${name}`, String(value));
    }
    return text;
}

// Ro'yxatlardagi matnlarni belgilash uchun (tarjima chizishda t() bilan qilinadi).
export const tk = (key) => key;
