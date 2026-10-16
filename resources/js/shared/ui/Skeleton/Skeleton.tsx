import type { HTMLAttributes } from 'react';
import { cn } from '@/shared/lib';

export default function Skeleton({
    className,
    ...rest
}: HTMLAttributes<HTMLDivElement>) {
    return (
        <div
            aria-hidden="true"
            className={cn(
                'animate-pulse rounded-control bg-neutral-200',
                className,
            )}
            {...rest}
        />
    );
}
