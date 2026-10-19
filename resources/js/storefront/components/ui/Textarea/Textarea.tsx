import { useId } from 'react';
import type { TextareaHTMLAttributes } from 'react';
import { cn } from '@/shared/lib';

export type TextareaProps = TextareaHTMLAttributes<HTMLTextAreaElement> & {
    label: string;
    error?: string;
};

export default function Textarea({
    label,
    error,
    id,
    className,
    ...rest
}: TextareaProps) {
    const generatedId = useId();
    const textareaId = id ?? generatedId;
    const errorId = `${textareaId}-error`;

    return (
        <div className="flex flex-col gap-1.5">
            <label
                htmlFor={textareaId}
                className="text-sm font-medium text-neutral-800"
            >
                {label}
            </label>
            <textarea
                id={textareaId}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? errorId : undefined}
                className={cn(
                    'min-h-24 w-full rounded-control border bg-white px-3 py-2.5 text-base outline-none placeholder:text-neutral-400',
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
