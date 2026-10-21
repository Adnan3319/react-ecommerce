import { Link } from '@inertiajs/react';
import { useEffect } from 'react';
import { cart } from '@/routes';
const ADDED_TOAST_MS = 4000;

type AddedToastProps = {
    onDismiss: () => void;
};

export default function AddedToast({ onDismiss }: AddedToastProps) {
    useEffect(() => {
        const timer = setTimeout(onDismiss, ADDED_TOAST_MS);

        return () => clearTimeout(timer);
    }, [onDismiss]);

    return (
        <div
            role="status"
            className="fixed inset-x-4 bottom-36 z-40 mx-auto flex max-w-sm items-center justify-between gap-4 rounded-control bg-neutral-900 px-4 py-3 text-sm text-white shadow-xl md:bottom-6"
        >
            <span>Added to cart</span>
            <Link
                href={cart()}
                className="text-accent-200 font-semibold underline-offset-2 hover:underline"
            >
                View cart
            </Link>
        </div>
    );
}
