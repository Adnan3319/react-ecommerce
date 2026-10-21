import { useEffect, useRef, useState } from 'react';
import { suggest } from '@/routes/search';
import { useDebouncedCallback } from '@/shared/hooks/useDebouncedCallback';
import {
    MIN_SEARCH_LENGTH,
    SUGGEST_DEBOUNCE_MS,
} from '@/storefront/config/search';
import type { SuggestResult } from '@/storefront/types/search';

function isAbort(error: unknown): boolean {
    return error instanceof DOMException && error.name === 'AbortError';
}

/**
 * Suggestions for the typed term, requested after a short pause. A newer request cancels the one
 * still in flight, so a slow answer can never replace a fresher one.
 */
export function useSearchSuggestions(category: string | null) {
    const [result, setResult] = useState<SuggestResult | null>(null);
    const [isLoading, setIsLoading] = useState(false);
    const controller = useRef<AbortController | null>(null);

    useEffect(() => () => controller.current?.abort(), []);

    const load = useDebouncedCallback((term: string) => {
        controller.current?.abort();

        if (term.length < MIN_SEARCH_LENGTH) {
            setResult(null);
            setIsLoading(false);

            return;
        }

        const request = new AbortController();
        controller.current = request;
        setIsLoading(true);

        fetch(
            suggest({ query: { q: term, ...(category ? { category } : {}) } })
                .url,
            {
                headers: { Accept: 'application/json' },
                signal: request.signal,
            },
        )
            .then((response) => {
                if (!response.ok) {
                    throw new Error(
                        `Suggest request failed: ${response.status}`,
                    );
                }

                return response.json() as Promise<{ data: SuggestResult }>;
            })
            .then((body) => {
                setResult(body.data);
                setIsLoading(false);
            })
            .catch((error: unknown) => {
                if (!isAbort(error)) {
                    setResult(null);
                    setIsLoading(false);
                }
            });
    }, SUGGEST_DEBOUNCE_MS);

    return { result, isLoading, request: load };
}
