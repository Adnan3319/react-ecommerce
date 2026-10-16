import { MAX_QUANTITY, MIN_QUANTITY } from '../../schemas';

type QuantityStepperProps = {
    value: number;
    onChange: (value: number) => void;
};

const BUTTON_CLASS =
    'flex h-11 w-11 items-center justify-center text-xl font-semibold text-neutral-800 transition-colors hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40';

export default function QuantityStepper({
    value,
    onChange,
}: QuantityStepperProps) {
    return (
        <div className="flex flex-col gap-1.5">
            <span className="text-sm font-medium text-neutral-800">
                Quantity
            </span>
            <div className="inline-flex w-fit items-center overflow-hidden rounded-control border border-neutral-300 bg-white">
                <button
                    type="button"
                    aria-label="Decrease quantity"
                    disabled={value <= MIN_QUANTITY}
                    onClick={() => onChange(value - 1)}
                    className={BUTTON_CLASS}
                >
                    &minus;
                </button>
                <output
                    aria-live="polite"
                    className="w-12 text-center text-base font-semibold"
                >
                    {value}
                </output>
                <button
                    type="button"
                    aria-label="Increase quantity"
                    disabled={value >= MAX_QUANTITY}
                    onClick={() => onChange(value + 1)}
                    className={BUTTON_CLASS}
                >
                    +
                </button>
            </div>
        </div>
    );
}
