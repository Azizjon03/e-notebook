/** Telefon raqam: +998 dan keyin 9 ta raqam. Server ham raqamni qayta tekshiradi. */
export function formatPhone(value) {
    let digits = value.replace(/\D/g, '');
    if (digits.startsWith('998')) digits = digits.slice(3);
    digits = digits.slice(0, 9);
    const parts = [digits.slice(0, 2), digits.slice(2, 5), digits.slice(5, 7), digits.slice(7, 9)].filter(Boolean);
    return `+998${parts.length ? ' ' + parts.join(' ') : ''}`;
}

export default function PhoneInput({ value, onChange, className = '', ...props }) {
    return (
        <input
            type="tel"
            inputMode="tel"
            autoComplete="tel"
            value={value}
            onChange={(e) => onChange(formatPhone(e.target.value))}
            placeholder="+998 90 123 45 67"
            className={className}
            {...props}
        />
    );
}
