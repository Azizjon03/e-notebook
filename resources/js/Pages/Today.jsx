import { Head } from '@inertiajs/react';
import AppLayout from '../Components/AppLayout';

export default function Today({ date }) {
    return (
        <AppLayout>
            <Head title="Bugun" />
            <section className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-line">
                <p className="text-sm font-medium text-muted">{date}</p>
                <h1 className="mt-1 text-2xl font-extrabold text-ink">Bugun</h1>
                <p className="mt-3 text-muted">
                    Loyiha asosi tayyor. Vazifalar, namoz vaqtlari va odatlar keyingi bosqichlarda qo'shiladi.
                </p>
            </section>
        </AppLayout>
    );
}
