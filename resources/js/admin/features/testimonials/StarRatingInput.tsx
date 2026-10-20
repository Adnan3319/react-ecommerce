import { cn } from '@admin/utils';

type StarRatingInputProps = {
    value: number;
    min: number;
    max: number;
    onChange: (rating: number) => void;
};

/** Pick 1..max stars: a radio group, so keyboard and screen readers work. */
export default function StarRatingInput({
    value,
    min,
    max,
    onChange,
}: StarRatingInputProps) {
    const ratings = Array.from({ length: max - min + 1 }, (_, i) => min + i);

    return (
        <div
            role="radiogroup"
            aria-label="Rating"
            className="flex items-center gap-1"
        >
            {ratings.map((rating) => (
                <button
                    key={rating}
                    type="button"
                    role="radio"
                    aria-checked={rating === value}
                    aria-label={`${rating} ${rating === 1 ? 'star' : 'stars'}`}
                    onClick={() => onChange(rating)}
                    className={cn(
                        'text-3xl leading-none transition-colors',
                        rating <= value
                            ? 'text-amber-400'
                            : 'text-gray-300 hover:text-amber-200 dark:text-gray-600',
                    )}
                >
                    ★
                </button>
            ))}
            <span className="ms-2 text-sm text-gray-500">
                {value} / {max}
            </span>
        </div>
    );
}
