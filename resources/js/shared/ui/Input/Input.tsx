import { useId } from 'react';
import type { InputHTMLAttributes } from 'react';
import { cn } from '@/shared/lib';

export type InputProps = InputHTMLAttributes<HTMLInputElement> & {
    label: string;
    error?: string;
};

export default function Input({
    label,
    error,
    id,
    className,
    ...rest
}: InputProps) {
    const generatedId = useId();
    const inputId = id ?? generatedId;
    const errorId = `${inputId}-error`;

    return (
        <div className="flex flex-col gap-1.5">
            <label
                htmlFor={inputId}
                className="text-sm font-medium text-neutral-800"
            >
                {label}
            </label>
            <input
                id={inputId}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? errorId : undefined}
                className={cn(
                    'h-11 w-full rounded-control border bg-white px-3 text-base outline-none placeholder:text-neutral-400',
                    'focus:border-accent-600 focus:ring-2 focus:ring-accent-100',
                    error ? 'border-red-500' : 'border-neutral-300',
                    className,
                )}
                {...rest}
            />
            {error && (
                <p id={errorId} role="alert" className="text-sm text-red-600">
                    {error}
                </p>
            )}
        </div>
    );
}
