import { useRef } from 'react';

const PREFIX = '+998 ';

/** 9 ta raqamni "+998 (90) 123-45-67" ko'rinishiga keltiradi. */
export function formatPhone(value) {
    const text = String(value ?? '').trim();
    let digits = text.replace(/\D/g, '');
    if (text.startsWith('+998') || (digits.length > 9 && digits.startsWith('998'))) digits = digits.slice(3);
    digits = digits.slice(0, 9);

    let out = PREFIX;
    if (digits.length > 0) out += `(${digits.slice(0, 2)}`;
    if (digits.length >= 2) out += ') ';
    if (digits.length > 2) out += digits.slice(2, 5);
    if (digits.length > 5) out += `-${digits.slice(5, 7)}`;
    if (digits.length > 7) out += `-${digits.slice(7, 9)}`;
    return out.trimEnd();
}

const localDigits = (value) => formatPhone(value).slice(PREFIX.length).replace(/\D/g, '');

/**
 * Telefon raqam uchun mask: +998 o'chmaydi, faqat raqam yoziladi.
 * Server ham raqamni qayta tekshiradi va +998XXXXXXXXX ko'rinishida saqlaydi.
 */
export default function PhoneInput({ value, onChange, className = '', ...props }) {
    const ref = useRef(null);

    const onKeyDown = (e) => {
        // Qavs yoki chiziqcha oldida Backspace bosilsa, oldingi raqam o'chadi.
        if (e.key !== 'Backspace') return;
        const el = ref.current;
        if (el.selectionStart !== el.selectionEnd) return;
        const before = el.value.slice(0, el.selectionStart);
        if (/\d$/.test(before) && before.length > PREFIX.length) return;
        e.preventDefault();
        onChange(formatPhone(localDigits(value).slice(0, -1)));
    };

    return (
        <input
            ref={ref}
            type="tel"
            inputMode="tel"
            autoComplete="tel"
            value={formatPhone(value)}
            onKeyDown={onKeyDown}
            onChange={(e) => {
                const raw = e.target.value;
                // "+998" ni qo'lda o'chirib, raqamni boshidan yozganda ham to'g'ri ishlaydi.
                const digits = raw.startsWith('+998') ? raw.slice(4).replace(/\D/g, '') : raw.replace(/\D/g, '');
                onChange(formatPhone(digits));
            }}
            placeholder="+998 (90) 123-45-67"
            className={className}
            {...props}
        />
    );
}
