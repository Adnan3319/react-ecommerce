import { useEffect, useRef } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/shared/lib';

type SheetProps = {
    open: boolean;
    onClose: () => void;
    /** Accessible name of the dialog. */
    label: string;
    /** `center`: a card from `sm` up. `drawer`: a full-height panel on the right from `sm` up. */
    placement?: 'center' | 'drawer';
    children: ReactNode;
};

/** Modal dialog: a bottom sheet on mobile, a centered card or a right drawer from `sm` up. Focus handling and Esc come from `<dialog>`. */
export default function Sheet({
    open,
    onClose,
    label,
    placement = 'center',
    children,
}: SheetProps) {
    const dialogRef = useRef<HTMLDialogElement>(null);

    useEffect(() => {
        const dialog = dialogRef.current;

        if (!dialog) {
            return;
        }

        if (open && !dialog.open) {
            dialog.showModal();
        } else if (!open && dialog.open) {
            dialog.close();
        }
    }, [open]);

    return (
        <dialog
            ref={dialogRef}
            aria-label={label}
            onClose={onClose}
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
            className={cn(
                'm-0 mt-auto max-h-[90dvh] w-full max-w-none overflow-y-auto rounded-t-card bg-white p-0 backdrop:bg-black/50',
                placement === 'center' &&
                    'sm:m-auto sm:max-w-md sm:rounded-card',
                placement === 'drawer' &&
                    'sm:mt-0 sm:ml-auto sm:h-dvh sm:max-h-dvh sm:max-w-md sm:rounded-none',
            )}
        >
            {children}
        </dialog>
    );
}
