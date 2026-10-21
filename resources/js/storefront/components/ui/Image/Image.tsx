import type { ComponentProps } from 'react';
import { cn } from '@/shared/lib';

export type ImageProps = Omit<
    ComponentProps<'img'>,
    'width' | 'height' | 'alt' | 'loading' | 'fetchPriority'
> & {
    src: string;
    alt: string;
    width: number;
    height: number;
    /** Above-the-fold / LCP image: eager load with high fetch priority. */
    priority?: boolean;
    /** First visible images that are not the LCP: eager load at normal priority. */
    eager?: boolean;
};

export default function Image({
    alt,
    width,
    height,
    priority = false,
    eager = false,
    className,
    ...rest
}: ImageProps) {
    const isEager = priority || eager;

    return (
        <img
            alt={alt}
            width={width}
            height={height}
            loading={isEager ? 'eager' : 'lazy'}
            fetchPriority={priority ? 'high' : 'auto'}
            decoding={priority ? 'sync' : 'async'}
            className={cn('h-auto max-w-full', className)}
            {...rest}
        />
    );
}
