import type { HTMLAttributes } from 'react';
import { cn } from '@/shared/lib';

const TONES = {
    neutral: 'bg-neutral-100 text-neutral-700',
    accent: 'bg-accent-100 text-accent-700',
    success: 'bg-green-100 text-green-700',
    warning: 'bg-amber-100 text-amber-800',
    danger: 'bg-red-100 text-red-700',
} as const;

export type BadgeProps = HTMLAttributes<HTMLSpanElement> & {
    tone?: keyof typeof TONES;
};

export default function Badge({
    tone = 'neutral',
    className,
    children,
    ...rest
}: BadgeProps) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                TONES[tone],
                className,
            )}
            {...rest}
        >
            {children}
        </span>
    );
}
