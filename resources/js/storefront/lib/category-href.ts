import { search } from '@/routes';
import type { RouteDefinition } from '@/wayfinder';

/** Category links open the search page filtered by that category. */
export function categoryHref(slug: string): RouteDefinition<'get'> {
    return search({ query: { category: slug } });
}
