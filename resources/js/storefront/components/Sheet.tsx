import { useEffect, useRef } from 'react';
import type { ReactNode } from 'react';

type SheetProps = {
    open: boolean;
    onClose: () => void;
    /** Accessible name of the dialog. */
    label: string;
    children: ReactNode;
};

/** Modal dialog: a bottom sheet on mobile, a centered card from `sm` up. Focus handling and Esc come from `<dialog>`. */
export default function Sheet({ open, onClose, label, children }: SheetProps) {
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
            className="m-0 mt-auto max-h-[90dvh] w-full max-w-none overflow-y-auto rounded-t-card bg-white p-0 backdrop:bg-black/50 sm:m-auto sm:max-w-md sm:rounded-card"
        >
            {children}
        </dialog>
    );
}
