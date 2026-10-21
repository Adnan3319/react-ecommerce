import { router } from '@inertiajs/react';
import { useId, useState } from 'react';
import type { ChangeEvent, FocusEvent, FormEvent, KeyboardEvent } from 'react';
import { search } from '@/routes';
import { cn } from '@/shared/lib';
import { Icon } from '@/storefront/components/ui';
import { MIN_SEARCH_LENGTH } from '@/storefront/config/search';
import { useSearchSuggestions } from '@/storefront/hooks/useSearchSuggestions';
import SuggestionList, { optionId } from './SuggestionList';

type SearchBoxProps = {
    /** The term shown when the box mounts; remount it (`key`) when the page's term changes. */
    defaultQuery: string;
    /** Slug of the category the search stays inside. */
    category: string | null;
    /** `dark` sits on the glass header, `light` on the page. */
    tone: 'dark' | 'light';
    inputId?: string;
    /** Called after the visitor chose a suggestion or submitted the search. */
    onNavigate?: () => void;
};

/**
 * Search field with a suggestion dropdown (↑/↓ move, Enter opens the row or searches, Esc closes).
 * Submitting searches the whole catalog, or the current category when there is one.
 */
export default function SearchBox({
    defaultQuery,
    category,
    tone,
    inputId,
    onNavigate,
}: SearchBoxProps) {
    const listId = useId();
    const [value, setValue] = useState(defaultQuery);
    const [isOpen, setIsOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const { result, isLoading, request } = useSearchSuggestions(category);

    const term = value.trim();
    const isPanelVisible = isOpen && term.length >= MIN_SEARCH_LENGTH;
    const items = result?.items ?? [];
    // The products plus the "See all" row.
    const rowCount = isPanelVisible && items.length > 0 ? items.length + 1 : 0;

    function close(): void {
        setIsOpen(false);
        setActiveIndex(-1);
    }

    function handleChange(event: ChangeEvent<HTMLInputElement>): void {
        setValue(event.target.value);
        setIsOpen(true);
        setActiveIndex(-1);
        request(event.target.value.trim());
    }

    function handleSubmit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        close();
        onNavigate?.();

        router.get(search().url, {
            ...(term ? { q: term } : {}),
            ...(category ? { category } : {}),
        });
    }

    function handleKeyDown(event: KeyboardEvent<HTMLInputElement>): void {
        if (event.key === 'Escape' && isPanelVisible) {
            event.preventDefault();
            close();

            return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            if (rowCount === 0) {
                return;
            }

            event.preventDefault();
            setActiveIndex((current) =>
                event.key === 'ArrowDown'
                    ? (current + 1) % rowCount
                    : current <= 0
                      ? rowCount - 1
                      : current - 1,
            );

            return;
        }

        if (
            event.key === 'Enter' &&
            activeIndex >= 0 &&
            activeIndex < items.length
        ) {
            event.preventDefault();
            close();
            onNavigate?.();
            router.visit(items[activeIndex].url);
        }
    }

    function handleBlur(event: FocusEvent<HTMLDivElement>): void {
        if (!event.currentTarget.contains(event.relatedTarget)) {
            close();
        }
    }

    return (
        <div className="relative w-full" onBlur={handleBlur}>
            <form
                role="search"
                action={search().url}
                method="get"
                onSubmit={handleSubmit}
                className={cn(
                    'flex w-full overflow-hidden rounded-full border',
                    tone === 'dark'
                        ? 'border-white/20 bg-white/10 text-white backdrop-blur focus-within:border-white/50'
                        : 'border-neutral-300 bg-white focus-within:border-accent-600 focus-within:ring-2 focus-within:ring-accent-100',
                )}
            >
                {category && (
                    <input type="hidden" name="category" value={category} />
                )}
                <input
                    id={inputId}
                    type="search"
                    name="q"
                    value={value}
                    onChange={handleChange}
                    onFocus={() => setIsOpen(true)}
                    onKeyDown={handleKeyDown}
                    role="combobox"
                    aria-expanded={isPanelVisible}
                    aria-controls={listId}
                    aria-autocomplete="list"
                    aria-activedescendant={
                        activeIndex >= 0
                            ? optionId(listId, activeIndex)
                            : undefined
                    }
                    placeholder="Search products..."
                    aria-label="Search products"
                    autoComplete="off"
                    className={cn(
                        'h-11 min-w-0 flex-1 bg-transparent pl-4 text-sm outline-none',
                        tone === 'dark'
                            ? 'placeholder:text-white/60'
                            : 'placeholder:text-neutral-400',
                    )}
                />
                <button
                    type="submit"
                    aria-label="Search"
                    className={cn(
                        'flex h-11 w-12 items-center justify-center transition-colors',
                        tone === 'dark'
                            ? 'text-white/80 hover:text-white'
                            : 'text-neutral-600 hover:text-accent-600',
                    )}
                >
                    <Icon name="search" size={18} />
                </button>
            </form>

            {isPanelVisible && (
                <SuggestionList
                    id={listId}
                    term={term}
                    category={category}
                    result={result}
                    isLoading={isLoading}
                    activeIndex={activeIndex}
                />
            )}
        </div>
    );
}
