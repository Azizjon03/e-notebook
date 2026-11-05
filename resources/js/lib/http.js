// Inertia sahifasini qayta yuklamasdan kichik so'rov yuborish (masalan, o'qilgan betni saqlash).
function xsrf() {
    const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

export function sendJson(method, url, data, { keepalive = false } = {}) {
    return fetch(url, {
        method,
        keepalive,
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrf(),
        },
        body: JSON.stringify(data),
    }).catch(() => null);
}
