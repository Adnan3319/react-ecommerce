import { useState } from 'react';
import type { KeyboardEvent } from 'react';
import { cn } from '@admin/utils';
import type { ChipResult } from '../useVariantMatrix';
import type { ValueChip } from '../types';

type ChipInputProps = {
    chips: ValueChip[];
    maxVariants: number;
    label: string;
    errors: Partial<Record<number, string>>;
    onAdd: (text: string) => ChipResult;
    onRename: (chipKey: string, text: string) => ChipResult;
    onRemove: (chipKey: string) => void;
};

const SEPARATOR = ',';

/** Value chips of one variant head: Enter or comma adds, × removes, double-click renames in place. */
export default function ChipInput({ chips, maxVariants, label, errors, onAdd, onRename, onRemove }: ChipInputProps) {
    const [draft, setDraft] = useState('');
    const [editing, setEditing] = useState<{ key: string; text: string } | null>(null);
    const [warning, setWarning] = useState<string | null>(null);

    const report = (result: ChipResult): boolean => {
        const messages: Record<ChipResult, string | null> = {
            added: null,
            empty: null,
            duplicate: 'This value is already in the list.',
            limit: `Too many combinations: a product can have at most ${maxVariants} variants.`,
        };

        setWarning(messages[result]);

        return result === 'added';
    };

    const commitDraft = (text: string) => {
        if (text.trim() === '') {
            return;
        }

        if (report(onAdd(text))) {
            setDraft('');
        }
    };

    const onDraftChange = (value: string) => {
        if (value.includes(SEPARATOR)) {
            commitDraft(value.split(SEPARATOR)[0] ?? '');

            return;
        }

        setDraft(value);
    };

    const onDraftKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            commitDraft(draft);
        }
    };

    const commitEdit = () => {
        if (editing && report(onRename(editing.key, editing.text))) {
            setEditing(null);
        } else if (editing?.text.trim() === '') {
            setEditing(null);
        }
    };

    const chipErrors = Object.values(errors).filter((message): message is string => Boolean(message));

    return (
        <div>
            <div className="flex min-h-11 flex-wrap items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 focus-within:border-brand-300 focus-within:ring-3 focus-within:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900">
                {chips.map((chip, index) =>
                    editing?.key === chip.key ? (
                        <input
                            key={chip.key}
                            autoFocus
                            aria-label={`Rename ${chip.value}`}
                            value={editing.text}
                            onChange={(event) => setEditing({ key: chip.key, text: event.target.value })}
                            onBlur={commitEdit}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter') {
                                    event.preventDefault();
                                    commitEdit();
                                }

                                if (event.key === 'Escape') {
                                    setEditing(null);
                                }
                            }}
                            className="h-7 w-28 rounded-md border border-brand-300 bg-white px-2 text-sm text-gray-800 outline-hidden dark:bg-gray-800 dark:text-white/90"
                        />
                    ) : (
                        <span
                            key={chip.key}
                            title="Double-click to rename"
                            onDoubleClick={() => setEditing({ key: chip.key, text: chip.value })}
                            className={cn(
                                'inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2.5 py-1 text-sm text-gray-800 dark:bg-white/10 dark:text-white/90',
                                errors[index] && 'ring-1 ring-error-500',
                            )}
                        >
                            {chip.value}
                            <button
                                type="button"
                                aria-label={`Remove ${chip.value}`}
                                onClick={() => onRemove(chip.key)}
                                className="text-gray-400 hover:text-error-500"
                            >
                                ×
                            </button>
                        </span>
                    ),
                )}
                <input
                    value={draft}
                    aria-label={`${label} values`}
                    placeholder="Type and press Enter..."
                    onChange={(event) => onDraftChange(event.target.value)}
                    onKeyDown={onDraftKeyDown}
                    onBlur={() => commitDraft(draft)}
                    className="min-w-40 flex-1 bg-transparent text-sm text-gray-800 outline-hidden placeholder:text-gray-400 dark:text-white/90"
                />
            </div>
            {warning && (
                <p role="status" className="mt-1.5 text-xs text-warning-600">
                    {warning}
                </p>
            )}
            {chipErrors.map((message) => (
                <p key={message} role="alert" className="mt-1.5 text-xs text-error-500">
                    {message}
                </p>
            ))}
        </div>
    );
}
