import { cn } from '@admin/utils';

type StarRatingProps = {
    value: number;
    max: number;
};

/** Read-only stars for the list (filled up to `value`). */
export default function StarRating({ value, max }: StarRatingProps) {
    return (
        <span
            role="img"
            aria-label={`${value} out of ${max} stars`}
            className="inline-flex gap-0.5 text-base leading-none"
        >
            {Array.from({ length: max }, (_, index) => (
                <span
                    key={index}
                    aria-hidden
                    className={cn(
                        index < value
                            ? 'text-amber-400'
                            : 'text-gray-300 dark:text-gray-600',
                    )}
                >
                    ★
                </span>
            ))}
        </span>
    );
}
