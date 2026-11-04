/**
 * Faqat raqam yoziladigan input: harf va belgilar kirmaydi, katta sonlar
 * "10 000" ko'rinishida guruhlanadi. onChange raqamlarning o'zini beradi ("10000").
 */
export function groupDigits(digits) {
    return String(digits ?? '').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

export default function NumberInput({ value, onChange, max, maxLength = 7, className = '', ...props }) {
    return (
        <input
            type="text"
            inputMode="numeric"
            pattern="[0-9 ]*"
            autoComplete="off"
            value={groupDigits(value)}
            onChange={(e) => {
                let digits = e.target.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, maxLength);
                if (max !== undefined && digits && Number(digits) > max) digits = String(max);
                onChange(digits);
            }}
            className={className}
            {...props}
        />
    );
}
