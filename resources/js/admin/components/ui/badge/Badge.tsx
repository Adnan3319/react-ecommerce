import type { FC, ReactNode } from 'react';
import { cn } from '@admin/utils';

const COLORS = {
    light: {
        primary:
            'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
        success:
            'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
        error: 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
        warning:
            'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400',
        info: 'bg-blue-light-50 text-blue-light-500 dark:bg-blue-light-500/15 dark:text-blue-light-500',
        light: 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-white/80',
        dark: 'bg-gray-500 text-white dark:bg-white/5 dark:text-white',
    },
    solid: {
        primary: 'bg-brand-500 text-white',
        success: 'bg-success-500 text-white',
        error: 'bg-error-500 text-white',
        warning: 'bg-warning-500 text-white',
        info: 'bg-blue-light-500 text-white',
        light: 'bg-gray-400 text-white dark:bg-white/5 dark:text-white/80',
        dark: 'bg-gray-700 text-white',
    },
} as const;

const SIZES = {
    sm: 'px-2 py-0.5 text-theme-xs',
    md: 'px-2.5 py-0.5 text-sm',
} as const;

interface BadgeProps {
    children: ReactNode;
    variant?: keyof typeof COLORS;
    size?: keyof typeof SIZES;
    color?: keyof (typeof COLORS)['light'];
    startIcon?: ReactNode;
    endIcon?: ReactNode;
    className?: string;
}

const Badge: FC<BadgeProps> = ({
    children,
    variant = 'light',
    size = 'md',
    color = 'primary',
    startIcon,
    endIcon,
    className,
}) => {
    return (
        <span
            className={cn(
                'inline-flex items-center justify-center gap-1 rounded-full font-medium',
                SIZES[size],
                COLORS[variant][color],
                className,
            )}
        >
            {startIcon}
            {children}
            {endIcon}
        </span>
    );
};

export default Badge;
