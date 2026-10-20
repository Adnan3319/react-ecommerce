import { formatMoney } from '@/shared/lib';
import { Badge } from '@/storefront/components/ui';

type PriceTagProps = {
    /** Minor units. */
    price: number;
    /** Minor units; the price actually charged when set. */
    salePrice: number | null;
};

export default function PriceTag({ price, salePrice }: PriceTagProps) {
    const current = salePrice ?? price;

    return (
        <p className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <span className="text-4xl font-extrabold tracking-tight text-neutral-900">
                {formatMoney(current)}
            </span>
            {salePrice !== null && (
                <>
                    <s className="text-lg text-neutral-400">
                        {formatMoney(price)}
                    </s>
                    <Badge tone="danger">
                        Save {formatMoney(price - salePrice)}
                    </Badge>
                </>
            )}
        </p>
    );
}
