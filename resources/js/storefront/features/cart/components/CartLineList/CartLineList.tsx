import type { CartLine } from '../../types';
import { CartLineItem } from '../CartLineItem';

type CartLineListProps = {
    lines: CartLine[];
    onQuantityChange: (lineId: number, quantity: number) => void;
    onRemove: (lineId: number) => void;
};

export default function CartLineList({
    lines,
    ...handlers
}: CartLineListProps) {
    return (
        <ul
            aria-label="Cart items"
            className="divide-y divide-neutral-200 rounded-card border border-neutral-200 bg-white px-4 shadow-card sm:px-5"
        >
            {lines.map((line) => (
                <CartLineItem key={line.id} line={line} {...handlers} />
            ))}
        </ul>
    );
}
