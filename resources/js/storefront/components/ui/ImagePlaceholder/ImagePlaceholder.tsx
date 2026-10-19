import type { HTMLAttributes } from 'react';
import { cn } from '@/shared/lib';
import { Icon } from '../Icon';

/** Stands in for a photo until real media is uploaded. */
export default function ImagePlaceholder({
    className,
    ...rest
}: HTMLAttributes<HTMLDivElement>) {
    return (
        <div
            aria-hidden="true"
            className={cn(
                'flex items-center justify-center bg-cream-100 text-accent-500/60',
                className,
            )}
            {...rest}
        >
            <Icon name="image" size={40} />
        </div>
    );
}
