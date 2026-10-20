import { cn } from '@admin/utils';
import type { CategoryNode } from '../types';
import FieldError from './FieldError';

type CategoryPickerProps = {
    tree: CategoryNode[];
    value: string;
    error?: string;
    onChange: (categoryId: string) => void;
};

type RadioProps = {
    id: number;
    label: string;
    checked: boolean;
    onSelect: () => void;
};

function CategoryRadio({ id, label, checked, onSelect }: RadioProps) {
    return (
        <label
            className={cn(
                'flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 text-sm',
                checked
                    ? 'border-brand-500 bg-brand-50 text-brand-500 dark:bg-brand-500/15'
                    : 'border-gray-200 text-gray-700 hover:border-gray-300 dark:border-gray-800 dark:text-gray-300',
            )}
        >
            <input
                type="radio"
                name="category_id"
                value={id}
                checked={checked}
                onChange={onSelect}
                className="h-4 w-4 accent-brand-500"
            />
            {label}
        </label>
    );
}

/** Parents are headers with their sub-categories as radios; a parent without sub-categories is a radio itself. */
export default function CategoryPicker({ tree, value, error, onChange }: CategoryPickerProps) {
    const selected = tree
        .flatMap((parent) =>
            parent.children.length === 0
                ? [{ id: parent.id, path: parent.name }]
                : parent.children.map((child) => ({ id: child.id, path: `${parent.name} › ${child.name}` })),
        )
        .find((option) => String(option.id) === value);

    return (
        <div>
            <div className="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                {tree.map((parent) =>
                    parent.children.length === 0 ? (
                        <div key={parent.id} className="p-3">
                            <CategoryRadio
                                id={parent.id}
                                label={parent.name}
                                checked={String(parent.id) === value}
                                onSelect={() => onChange(String(parent.id))}
                            />
                        </div>
                    ) : (
                        <div key={parent.id}>
                            <div className="flex items-center justify-between bg-gray-50 px-4 py-2.5 text-sm dark:bg-white/5">
                                <span className="font-medium text-gray-900 dark:text-white/90">{parent.name}</span>
                                <span className="text-xs text-gray-500">{parent.children.length} sub-categories</span>
                            </div>
                            <div className="grid gap-3 p-3 sm:grid-cols-2 lg:grid-cols-3">
                                {parent.children.map((child) => (
                                    <CategoryRadio
                                        key={child.id}
                                        id={child.id}
                                        label={child.name}
                                        checked={String(child.id) === value}
                                        onSelect={() => onChange(String(child.id))}
                                    />
                                ))}
                            </div>
                        </div>
                    ),
                )}
            </div>
            <p className="mt-2 text-sm text-gray-500">
                Selected: <strong className="text-brand-500">{selected?.path ?? 'none'}</strong>
            </p>
            <FieldError message={error} />
        </div>
    );
}
