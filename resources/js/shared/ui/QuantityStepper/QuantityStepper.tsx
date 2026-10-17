import { cn } from '@/shared/lib';

type QuantityStepperProps = {
    value: number;
    min: number;
    max: number;
    onChange: (value: number) => void;
    /** Visible label above the control; omit in dense layouts such as cart rows. */
    label?: string;
    size?: 'sm' | 'md';
    disabled?: boolean;
};

const BUTTON_SIZES = {
    sm: 'h-9 w-9 text-lg',
    md: 'h-11 w-11 text-xl',
} as const;

export default function QuantityStepper({
    value,
    min,
    max,
    onChange,
    label,
    size = 'md',
    disabled = false,
}: QuantityStepperProps) {
    const buttonClass = cn(
        'flex items-center justify-center font-semibold text-neutral-800 transition-colors hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40',
        BUTTON_SIZES[size],
    );

    return (
        <div className="flex flex-col gap-1.5">
            {label && (
                <span className="text-sm font-medium text-neutral-800">
                    {label}
                </span>
            )}
            <div className="inline-flex w-fit items-center overflow-hidden rounded-control border border-neutral-300 bg-white">
                <button
                    type="button"
                    aria-label="Decrease quantity"
                    disabled={disabled || value <= min}
                    onClick={() => onChange(value - 1)}
                    className={buttonClass}
                >
                    &minus;
                </button>
                <output
                    aria-live="polite"
                    className={cn(
                        'text-center font-semibold',
                        size === 'sm' ? 'w-9 text-sm' : 'w-12 text-base',
                    )}
                >
                    {value}
                </output>
                <button
                    type="button"
                    aria-label="Increase quantity"
                    disabled={disabled || value >= max}
                    onClick={() => onChange(value + 1)}
                    className={buttonClass}
                >
                    +
                </button>
            </div>
        </div>
    );
}
