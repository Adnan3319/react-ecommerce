import type { ImgHTMLAttributes } from 'react';
import { cn } from '@/shared/lib';

export type ImageProps = Omit<
    ImgHTMLAttributes<HTMLImageElement>,
    'width' | 'height' | 'alt' | 'loading' | 'fetchPriority'
> & {
    src: string;
    alt: string;
    width: number;
    height: number;
    /** Above-the-fold / LCP image: eager load with high fetch priority. */
    priority?: boolean;
};

export default function Image({
    alt,
    width,
    height,
    priority = false,
    className,
    ...rest
}: ImageProps) {
    return (
        <img
            alt={alt}
            width={width}
            height={height}
            loading={priority ? 'eager' : 'lazy'}
            fetchPriority={priority ? 'high' : 'auto'}
            decoding={priority ? 'sync' : 'async'}
            className={cn('h-auto max-w-full', className)}
            {...rest}
        />
    );
}
