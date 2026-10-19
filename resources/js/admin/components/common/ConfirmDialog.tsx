import type { ReactNode } from "react";
import Button from "@admin/components/ui/button/Button";
import { Modal } from "@admin/components/ui/modal";

interface ConfirmDialogProps {
  isOpen: boolean;
  title: string;
  message: ReactNode;
  confirmLabel: string;
  onConfirm: () => void;
  onCancel: () => void;
  processing?: boolean;
  confirmVariant?: "primary" | "danger";
}

const noop = () => undefined;

export default function ConfirmDialog({
  isOpen,
  title,
  message,
  confirmLabel,
  onConfirm,
  onCancel,
  processing = false,
  confirmVariant = "danger",
}: ConfirmDialogProps) {
  return (
    <Modal
      isOpen={isOpen}
      onClose={processing ? noop : onCancel}
      showCloseButton={false}
      className="max-w-md p-6"
    >
      <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
        {title}
      </h2>
      <div className="mt-2 text-sm text-gray-600 dark:text-gray-400">
        {message}
      </div>

      <div className="mt-6 flex justify-end gap-3">
        <Button
          variant="outline"
          size="sm"
          onClick={onCancel}
          disabled={processing}
        >
          Cancel
        </Button>
        <Button
          variant={confirmVariant}
          size="sm"
          onClick={onConfirm}
          disabled={processing}
        >
          {confirmLabel}
        </Button>
      </div>
    </Modal>
  );
}
