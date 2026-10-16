import { Link } from '@inertiajs/react';
import type { RouteDefinition } from '@/wayfinder';
import { Icon } from '../Icon';

type SectionHeaderProps = {
    title: string;
    viewAll?: {
        label: string;
        href: RouteDefinition<'get'>;
    };
};

export default function SectionHeader({ title, viewAll }: SectionHeaderProps) {
    return (
        <div className="mb-6 flex items-end justify-between gap-4">
            <h2 className="text-xl font-bold tracking-tight text-neutral-900 sm:text-2xl">
                {title}
            </h2>
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
