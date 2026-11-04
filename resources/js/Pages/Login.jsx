import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { isMiniApp, webApp } from '../telegram/webapp';

export default function Login() {
    const { errors } = usePage().props;
    const [trying, setTrying] = useState(false);

    useEffect(() => {
        // Mini App ichida initData bor: avtomatik kiramiz.
        if (!isMiniApp()) return;
        setTrying(true);
        router.post(
            '/auth/telegram/webapp',
            { init_data: webApp().initData },
            { onFinish: () => setTrying(false) },
        );
    }, []);

    return (
        <div className="mx-auto grid min-h-screen max-w-md place-items-center px-4">
            <Head title="Kirish" />
            <div className="w-full rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-line">
                <span className="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-g700 text-2xl font-extrabold text-white">
                    e
                </span>
                <h1 className="mt-4 text-2xl font-extrabold text-ink">e-notebook</h1>
                {trying ? (
                    <p className="mt-3 text-muted">Telegram orqali kirilmoqda...</p>
                ) : (
                    <p className="mt-3 text-muted">
                        Ilovani Telegram bot ichidagi "Ilovani ochish" tugmasi orqali oching.
                    </p>
                )}
                {errors?.init_data && <p className="mt-3 text-sm font-semibold text-red">{errors.init_data}</p>}
            </div>
        </div>
    );
}
