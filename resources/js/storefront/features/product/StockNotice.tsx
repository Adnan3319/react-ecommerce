import { cn } from '@/shared/lib';
import type { StockState } from '@/storefront/types/variants';

type StockNoticeProps = {
    state: StockState;
    stock: number;
};

export default function StockNotice({ state, stock }: StockNoticeProps) {
    const isOut = state === 'out';
    const text = isOut
        ? 'Out of stock'
        : state === 'low'
          ? `In stock · ${stock} left`
          : 'In stock';

    return (
        <p
            className={cn(
                'flex items-center gap-2 text-sm font-medium',
                isOut ? 'text-red-600' : 'text-green-700',
            )}
        >
            <span
                aria-hidden="true"
                className={cn(
                    'h-2 w-2 rounded-full',
                    isOut ? 'bg-red-500' : 'bg-green-600',
                )}
            />
            {text}
        </p>
    );
}
