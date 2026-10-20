import { cn } from '@admin/utils';

interface StatusToggleProps {
    isActive: boolean;
    onToggle: () => void;
    disabled?: boolean;
    activeLabel?: string;
    inactiveLabel?: string;
}

export default function StatusToggle({
    isActive,
    onToggle,
    disabled = false,
    activeLabel = 'Active',
    inactiveLabel = 'Inactive',
}: StatusToggleProps) {
    return (
        <button
            type="button"
            onClick={onToggle}
            disabled={disabled}
            title="Click to change status"
            className={cn(
                'inline-flex rounded-full px-3 py-1 text-xs font-semibold transition disabled:opacity-60',
                isActive
                    ? 'bg-success-50 text-success-700 hover:bg-success-100 dark:bg-success-500/15 dark:text-success-500'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-400',
            )}
        >
            {isActive ? activeLabel : inactiveLabel}
        </button>
    );
}
