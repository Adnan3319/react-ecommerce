import { Icon } from '@/storefront/components/ui';

const MAX_STARS = 5;

type RatingStarsProps = {
    rating: number;
    reviewCount: number;
};

export default function RatingStars({ rating, reviewCount }: RatingStarsProps) {
    return (
        <p
            className="flex items-center gap-1 text-xs text-neutral-600"
            aria-label={`Rated ${rating} out of ${MAX_STARS} from ${reviewCount} reviews`}
        >
            <Icon
                name="star"
                size={14}
                fill="currentColor"
                className="text-amber-400"
            />
            <span className="font-semibold text-neutral-800">
                {rating.toFixed(1)}
            </span>
            <span>({reviewCount})</span>
        </p>
    );
}
