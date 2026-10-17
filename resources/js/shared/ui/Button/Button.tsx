import type { ButtonHTMLAttributes } from 'react';
import { cn } from '@/shared/lib';

const VARIANTS = {
    primary: 'bg-accent-600 text-white hover:bg-accent-700',
    secondary: 'bg-neutral-900 text-white hover:bg-neutral-800',
    outline:
        'border border-neutral-300 bg-white text-neutral-900 hover:bg-neutral-50',
    soft: 'bg-accent-50 text-accent-700 hover:bg-accent-100',
    ghost: 'text-neutral-700 hover:bg-neutral-100',
} as const;

const SIZES = {
    sm: 'h-9 px-3 text-sm',
    md: 'h-11 px-5 text-base',
    lg: 'h-12 px-6 text-base',
} as const;

export type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: keyof typeof VARIANTS;
    size?: keyof typeof SIZES;
    fullWidth?: boolean;
    loading?: boolean;
};

export default function Button({
    variant = 'primary',
    size = 'md',
    fullWidth = false,
    loading = false,
    type = 'button',
    disabled,
    className,
    children,
    ...rest
}: ButtonProps) {
    return (
        <button
            type={type}
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-control font-semibold transition-[color,background-color,transform] active:scale-[0.98]',
                'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600',
                'disabled:cursor-not-allowed disabled:opacity-60',
                VARIANTS[variant],
                SIZES[size],
                fullWidth && 'w-full',
                className,
            )}
            {...rest}
        >
            {children}
        </button>
    );
}
