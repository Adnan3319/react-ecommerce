import { usePage } from '@inertiajs/react';
import type { SharedProps } from '@/shared/types';
import { cn } from '@admin/utils';

type BrandMarkProps = {
    /** Show the store name next to the logo. */
    showName?: boolean;
    className?: string;
    nameClassName?: string;
};

/** The uploaded logo, or the first letter of the store name until one is uploaded (Company settings). */
export default function BrandMark({
    showName = true,
    className,
    nameClassName,
}: BrandMarkProps) {
    const { branding } = usePage<SharedProps>().props;

    return (
        <span className={cn('flex items-center gap-3', className)}>
            {branding.logo_url ? (
                <img
                    src={branding.logo_url}
                    alt={branding.name}
                    className="size-9 shrink-0 rounded-lg object-contain"
                />
            ) : (
                <span
                    aria-hidden="true"
                    className="bg-brand-500 flex size-9 shrink-0 items-center justify-center rounded-lg text-lg font-bold text-white"
                >
                    {branding.name.charAt(0).toUpperCase()}
                </span>
            )}
            {showName && (
                <span
                    className={cn(
                        'truncate text-lg font-bold text-gray-800 dark:text-white/90',
                        nameClassName,
                    )}
                >
                    {branding.name}
                </span>
            )}
        </span>
    );
}
