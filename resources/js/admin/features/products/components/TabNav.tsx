import { cn } from '@admin/utils';
import { TABS } from '../constants';
import type { TabId } from '../constants';

type TabNavProps = {
    tab: TabId;
    visited: TabId[];
    errorTabs: TabId[];
    onSelect: (tab: TabId) => void;
};

/** Tab header: active (blue), error (red), done (green: visited and error-free). */
export default function TabNav({ tab, visited, errorTabs, onSelect }: TabNavProps) {
    return (
        <nav
            aria-label="Product form steps"
            className="flex gap-2 overflow-x-auto rounded-xl border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-white/3"
        >
            {TABS.map((item, index) => {
                const hasError = errorTabs.includes(item.id);
                const isActive = item.id === tab;
                const isDone = !hasError && visited.includes(item.id) && !isActive;

                return (
                    <button
                        key={item.id}
                        type="button"
                        aria-current={isActive ? 'step' : undefined}
                        onClick={() => onSelect(item.id)}
                        className={cn(
                            'flex min-w-36 flex-1 items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition',
                            isActive
                                ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/15'
                                : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/5',
                            hasError && 'text-error-600 dark:text-error-500',
                        )}
                    >
                        <span
                            className={cn(
                                'flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-xs',
                                isActive && !hasError && 'border-brand-500 bg-brand-500 text-white',
                                isDone && 'border-success-500 text-success-600',
                                hasError && 'border-error-500 bg-error-50 text-error-600',
                                !isActive && !isDone && !hasError && 'border-gray-300 text-gray-500',
                            )}
                        >
                            {hasError ? '!' : isDone ? '✓' : index + 1}
                        </span>
                        {item.label}
                    </button>
                );
            })}
        </nav>
    );
}
