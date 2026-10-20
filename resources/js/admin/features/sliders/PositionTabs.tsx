import { cn } from '@admin/utils';
import { POSITION_ALL } from './constants';
import type { PositionOption, SliderPosition } from './types';

type PositionTabsProps = {
    positions: PositionOption[];
    active: SliderPosition | null;
    onChange: (position: SliderPosition | null) => void;
};

/** "All / Hero / Promo" tabs above the slider list; the choice lives in the URL (`position`). */
export default function PositionTabs({
    positions,
    active,
    onChange,
}: PositionTabsProps) {
    const tabs = [
        { value: POSITION_ALL, label: 'All' },
        ...positions.map(({ value, label }) => ({ value, label })),
    ];

    return (
        <nav
            aria-label="Slider position"
            className="mb-4 flex gap-1 rounded-lg border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-white/3"
        >
            {tabs.map((tab) => {
                const selected = (active ?? POSITION_ALL) === tab.value;

                return (
                    <button
                        key={tab.value}
                        type="button"
                        aria-current={selected ? 'page' : undefined}
                        onClick={() =>
                            onChange(
                                tab.value === POSITION_ALL
                                    ? null
                                    : (tab.value as SliderPosition),
                            )
                        }
                        className={cn(
                            'rounded-lg px-4 py-2 text-sm font-medium',
                            selected
                                ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/15'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400',
                        )}
                    >
                        {tab.label}
                    </button>
                );
            })}
        </nav>
    );
}
