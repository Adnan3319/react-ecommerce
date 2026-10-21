import { show } from '@/routes/categories';
import type { RouteDefinition } from '@/wayfinder';

export function categoryHref(slug: string): RouteDefinition<'get'> {
    return show(slug);
}
