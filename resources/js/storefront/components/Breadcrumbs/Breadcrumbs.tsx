import { Link } from '@inertiajs/react';
import type { RouteDefinition } from '@/wayfinder';

export type BreadcrumbItem = {
    label: string;
    /** Omit for the current page. */
    href?: RouteDefinition<'get'>;
};

type BreadcrumbsProps = {
    items: BreadcrumbItem[];
};

export default function Breadcrumbs({ items }: BreadcrumbsProps) {
    return (
        <nav aria-label="Breadcrumb">
            <ol className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-neutral-600">
                {items.map((item, index) => (
                    <li key={item.label} className="flex items-center gap-2">
                        {item.href ? (
                            <Link
                                href={item.href}
                                className="hover:text-accent-600"
                            >
                                {item.label}
                            </Link>
                        ) : (
                            <span
                                aria-current="page"
                                className="font-medium text-neutral-900"
                            >
                                {item.label}
                            </span>
                        )}
                        {index < items.length - 1 && (
                            <span aria-hidden="true">/</span>
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}
