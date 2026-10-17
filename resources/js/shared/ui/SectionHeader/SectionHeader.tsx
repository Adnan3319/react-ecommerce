import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { RouteDefinition } from '@/wayfinder';
import { Icon } from '../Icon';

type SectionHeaderProps = {
    title: string;
    subtitle?: string;
    viewAll?: {
        label: string;
        href: RouteDefinition<'get'>;
    };
    /** Extra control on the right, such as filter tabs. */
    action?: ReactNode;
    /** Small accent bar before the title. */
    accent?: boolean;
};

export default function SectionHeader({
    title,
    subtitle,
    viewAll,
    action,
    accent = false,
}: SectionHeaderProps) {
    return (
        <div className="mb-6 flex flex-wrap items-end justify-between gap-x-4 gap-y-3">
            <div>
                <h2 className="flex items-center gap-2 text-xl font-bold tracking-tight text-neutral-900 sm:text-2xl">
                    {accent && (
                        <span
                            aria-hidden="true"
                            className="h-6 w-1 rounded-full bg-accent-600"
                        />
                    )}
                    {title}
                </h2>
                {subtitle && (
                    <p className="mt-1 text-sm text-neutral-600">{subtitle}</p>
                )}
            </div>
            {action}
            {viewAll && (
                <Link
                    href={viewAll.href}
                    className="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-accent-600 hover:text-accent-700"
                >
                    {viewAll.label}
                    <Icon name="arrowRight" size={16} />
                </Link>
            )}
        </div>
    );
}
