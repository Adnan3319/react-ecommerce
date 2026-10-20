import { cn } from '@/shared/lib';
import type { PageHead } from './types';

type VariantPickerProps = {
    heads: PageHead[];
    selected: Record<number, number>;
    isAvailable: (headId: number, valueId: number) => boolean;
    onSelect: (headId: number, valueId: number) => void;
};

/** One row of buttons per head (Size, Color). Values with no stock for the other picks are struck through. */
export default function VariantPicker({ heads, selected, isAvailable, onSelect }: VariantPickerProps) {
    return (
        <div className="flex flex-col gap-4">
            {heads.map((head) => {
                const current = head.values.find((value) => value.id === selected[head.id]);

                return (
                    <fieldset key={head.id} className="flex flex-col gap-2">
                        <legend className="mb-1 text-sm font-semibold text-neutral-900">
                            {head.name}:{' '}
                            <span className="font-normal text-neutral-600">{current?.value}</span>
                        </legend>
                        <div className="flex flex-wrap gap-2">
                            {head.values.map((value) => {
                                const available = isAvailable(head.id, value.id);
                                const isSelected = value.id === selected[head.id];

                                return (
                                    <button
                                        key={value.id}
                                        type="button"
                                        disabled={!available}
                                        aria-pressed={isSelected}
                                        onClick={() => onSelect(head.id, value.id)}
                                        className={cn(
                                            'min-h-11 min-w-12 rounded-control border px-4 text-sm font-medium transition',
                                            isSelected
                                                ? 'border-neutral-900 bg-neutral-900 text-white'
                                                : 'border-neutral-300 bg-white text-neutral-900 hover:border-neutral-500',
                                            !available &&
                                                'cursor-not-allowed border-neutral-200 bg-neutral-100 text-neutral-400 line-through hover:border-neutral-200',
                                        )}
                                    >
                                        {value.value}
                                    </button>
                                );
                            })}
                        </div>
                    </fieldset>
                );
            })}
        </div>
    );
}
