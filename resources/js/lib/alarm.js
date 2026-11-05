// Taymer tugaganda bildirish: ovoz, tebranish, Telegram haptic va (sahifa yopiq bo'lsa) brauzer xabari.
// Brauzerlar ovozni faqat foydalanuvchi bosgandan keyin ruxsat beradi, shuning uchun
// "Boshlash" bosilganda prepareAlarm() chaqiriladi.
import { webApp } from '../telegram/webapp';

let ctx = null;

function prepareAudio() {
    try {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (Ctx && !ctx) ctx = new Ctx();
        ctx?.resume?.();
    } catch {
        ctx = null;
    }
}

// Sahifa qayta ochilganda ham ovoz ishlashi uchun birinchi bosishda tayyorlanadi.
if (typeof window !== 'undefined') {
    window.addEventListener('pointerdown', prepareAudio, {
        once: true,
        capture: true,
    });
}

export function prepareAlarm() {
    prepareAudio();
    try {
        if ('Notification' in window && Notification.permission === 'default' && !webApp()?.initData) {
            Notification.requestPermission().catch(() => {});
        }
    } catch {
        // Ba'zi brauzerlarda (Telegram ichida) Notification yo'q.
    }
}

function beep(at, freq, length) {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = 'sine';
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(0.0001, at);
    gain.gain.exponentialRampToValueAtTime(0.35, at + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, at + length);
    osc.connect(gain).connect(ctx.destination);
    osc.start(at);
    osc.stop(at + length + 0.05);
}

export function ringAlarm(title, body) {
    try {
        if (ctx) {
            ctx.resume?.();
            const t0 = ctx.currentTime + 0.05;
            [0, 0.35, 0.7, 1.4, 1.75, 2.1].forEach((d, i) => beep(t0 + d, i % 3 === 2 ? 1046 : 880, 0.25));
        }
    } catch {
        // Ovoz chiqmasa ham qolgan usullar ishlaydi.
    }
    navigator.vibrate?.([300, 150, 300, 150, 600]);
    webApp()?.HapticFeedback?.notificationOccurred('success');
    try {
        if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
            new Notification(title, { body, tag: 'enotebook-timer' });
        }
    } catch {
        // Android Chrome'da sahifadan to'g'ridan-to'g'ri Notification ishlamasligi mumkin.
    }
}
