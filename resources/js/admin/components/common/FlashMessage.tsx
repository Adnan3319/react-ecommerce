import { usePage } from "@inertiajs/react";

/**
 * Shows the "success" flash set by the backend (Inertia::flash) after a write.
 * Mounted once in AppLayout, so every admin page gets it.
 */
export default function FlashMessage() {
  const { flash } = usePage();
  const message = typeof flash.success === "string" ? flash.success : null;

  if (!message) {
    return null;
  }

  return (
    <p
      role="status"
      className="mb-4 rounded-lg bg-success-50 px-4 py-3 text-sm text-success-700 dark:bg-success-500/15 dark:text-success-500"
    >
      {message}
    </p>
  );
}
