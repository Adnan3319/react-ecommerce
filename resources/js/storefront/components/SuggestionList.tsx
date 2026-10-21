import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import { search } from '@/routes';
import { cn, formatMoney } from '@/shared/lib';
import { Image, ImagePlaceholder } from '@/storefront/components/ui';
import type { SuggestResult } from '@/storefront/types/search';

const THUMB_SIZE = 48;

type SuggestionListProps = {
    id: string;
    term: string;
    category: string | null;
    result: SuggestResult | null;
    isLoading: boolean;
    /** Index of the keyboard-highlighted row; the "See all" row comes after the products. */
    activeIndex: number;
};

export function optionId(listId: string, index: number): string {
    return `${listId}-option-${index}`;
}

function escapeRegExp(value: string): string {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/** Wraps every word of the term found in `text` in <mark>. */
function Highlight({ text, term }: { text: string; term: string }) {
    const words = term.split(/\s+/).filter(Boolean).map(escapeRegExp);

    if (words.length === 0) {
        return text;
    }

    const parts = text.split(new RegExp(`(${words.join('|')})`, 'i'));
    const matcher = new RegExp(`^(?:${words.join('|')})$`, 'i');

    return parts.map((part, index) => (
        <Fragment key={index}>
            {matcher.test(part) ? (
                <mark className="rounded-sm bg-accent-100 text-inherit">
                    {part}
                </mark>
            ) : (
                part
            )}
        </Fragment>
    ));
}

/** The dropdown under a search box: matching products and a link to all results. */
export default function SuggestionList({
    id,
    term,
    category,
    result,
    isLoading,
    activeIndex,
}: SuggestionListProps) {
    const items = result?.items ?? [];
    const seeAllIndex = items.length;

    if (items.length === 0 && !isLoading && result === null) {
        return null;
    }

    return (
        <div
            // Keeps the focus in the input, so a click on a row is not lost to a blur.
            onMouseDown={(event) => event.preventDefault()}
            className="absolute inset-x-0 top-full z-50 mt-2 overflow-hidden rounded-card bg-white text-neutral-900 shadow-card ring-1 ring-neutral-200"
        >
            {items.length > 0 ? (
                <ul id={id} role="listbox" aria-label="Product suggestions">
                    {items.map((item, index) => (
                        <li
                            key={item.url}
                            id={optionId(id, index)}
                            role="option"
                            aria-selected={activeIndex === index}
                            className={cn(
                                activeIndex === index && 'bg-cream-50',
                            )}
                        >
                            <Link
                                href={item.url}
                                tabIndex={-1}
                                className="flex items-center gap-3 px-3 py-2 hover:bg-cream-50"
                            >
                                {item.thumb ? (
                                    <Image
                                        src={item.thumb}
                                        alt=""
                                        width={THUMB_SIZE}
                                        height={THUMB_SIZE}
                                        className="size-12 shrink-0 rounded-control object-cover"
                                    />
                                ) : (
                                    <ImagePlaceholder className="size-12 shrink-0 rounded-control" />
                                )}
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-medium">
                                        <Highlight
                                            text={item.name}
                                            term={term}
                                        />
                                    </span>
                                    {item.category && (
                                        <span className="block truncate text-xs text-neutral-500">
                                            {item.category}
                                        </span>
                                    )}
                                </span>
                                {item.price !== null && (
                                    <span className="shrink-0 text-sm font-semibold">
                                        {formatMoney(item.price)}
                                    </span>
                                )}
                            </Link>
                        </li>
                    ))}
                    <li
                        id={optionId(id, seeAllIndex)}
                        role="option"
                        aria-selected={activeIndex === seeAllIndex}
                        className={cn(
                            'border-t border-neutral-200',
                            activeIndex === seeAllIndex && 'bg-cream-50',
                        )}
                    >
                        <Link
                            href={search({
                                query: {
                                    q: term,
                                    ...(category ? { category } : {}),
                                },
                            })}
                            tabIndex={-1}
                            className="block px-3 py-3 text-center text-sm font-semibold text-accent-600 hover:bg-cream-50"
                        >
                            See all {result?.total} results →
                        </Link>
                    </li>
                </ul>
            ) : (
                <p role="status" className="px-4 py-3 text-sm text-neutral-600">
                    {isLoading
                        ? 'Searching…'
                        : `No products found for “${term}”`}
                </p>
            )}
        </div>
    );
}
