import Badge from '@admin/components/ui/badge/Badge';
import { cn } from '@admin/utils';
import { FILTER_ALL } from './constants';
import type { OrderStatusTab } from './types';

type StatusTabsProps = {
    statuses: OrderStatusTab[];
    active: string | null;
    onChange: (status: string) => void;
};

export default function StatusTabs({ statuses, active, onChange }: StatusTabsProps) {
    const total = statuses.reduce((sum, status) => sum + status.count, 0);
    const tabs = [{ value: FILTER_ALL, label: 'All', count: total }, ...statuses];

    return (
        <nav
            aria-label="Order status"
            className="mb-4 flex gap-1 overflow-x-auto rounded-lg border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-white/3"
        >
            {tabs.map((tab) => {
                const selected = (active ?? FILTER_ALL) === tab.value;

                return (
                    <button
                        key={tab.value}
                        type="button"
                        aria-current={selected ? 'page' : undefined}
                        onClick={() => onChange(tab.value)}
                        className={cn(
                            'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap',
                            selected
                                ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/15'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400',
                        )}
                    >
                        {tab.label}
                        <Badge size="sm" color={selected ? 'primary' : 'light'}>
                            {tab.count}
                        </Badge>
                    </button>
                );
            })}
        </nav>
    );
}
