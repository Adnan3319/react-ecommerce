import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@admin/components/common/ConfirmDialog';
import Button from '@admin/components/ui/button/Button';
import { formatFull } from '@admin/lib/format-date';
import { destroy, index } from '@/routes/admin/messages';
import type { MessageShowProps } from './types';

export default function MessageDetail({ message, orders }: MessageShowProps) {
    const [confirming, setConfirming] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const remove = () =>
        router.delete(destroy(message.id).url, {
            onStart: () => setDeleting(true),
            onFinish: () => setDeleting(false),
        });

    return (
        <div className="w-full max-w-3xl">
            <Link
                href={index().url}
                className="hover:text-brand-500 mb-4 inline-block text-sm text-gray-500"
            >
                ← Back to messages
            </Link>

            <div className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">
                            {message.name}
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            {formatFull(message.created_at)}
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setConfirming(true)}
                    >
                        Delete
                    </Button>
                </div>

                <dl className="mt-6 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt className="text-xs font-medium text-gray-400 uppercase">
                            Mobile
                        </dt>
                        <dd className="mt-1 text-sm font-semibold text-gray-800 dark:text-white/90">
                            <a
                                href={`tel:${message.mobile}`}
                                className="text-brand-500 hover:underline"
                            >
                                {message.mobile}
                            </a>
                        </dd>
                    </div>
                    {message.email && (
                        <div>
                            <dt className="text-xs font-medium text-gray-400 uppercase">
                                Email
                            </dt>
                            <dd className="mt-1 text-sm font-semibold text-gray-800 dark:text-white/90">
                                <a
                                    href={`mailto:${message.email}`}
                                    className="text-brand-500 hover:underline"
                                >
                                    {message.email}
                                </a>
                            </dd>
                        </div>
                    )}
                </dl>

                <p className="mt-6 text-sm leading-relaxed whitespace-pre-line text-gray-700 dark:text-gray-300">
                    {message.message}
                </p>

                {orders.url !== null && (
                    <Link
                        href={orders.url}
                        className="text-brand-500 mt-6 inline-block text-sm font-medium hover:underline"
                    >
                        View {orders.count}{' '}
                        {orders.count === 1 ? 'order' : 'orders'} from this
                        number →
                    </Link>
                )}
            </div>

            <ConfirmDialog
                isOpen={confirming}
                title="Delete message"
                message="This message will be permanently deleted."
                confirmLabel="Delete"
                confirmVariant="danger"
                processing={deleting}
                onConfirm={remove}
                onCancel={() => setConfirming(false)}
            />
        </div>
    );
}
