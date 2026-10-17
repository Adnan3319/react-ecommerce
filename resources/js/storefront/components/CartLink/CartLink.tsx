import { useEffect, useRef, useState } from 'react';
import type { PointerEvent } from 'react';
import { Icon } from '@/shared/ui';
import { useCart } from '@/storefront/features/cart';
import CartPopover from './CartPopover';

/** Hover (mouse) or click/tap opens a cart preview; the current page stays put. */
export default function CartLink() {
    const { lines, itemCount, subtotal, remove, clear } = useCart();
    const [isOpen, setIsOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        function handlePointerDown(event: globalThis.PointerEvent): void {
            if (!containerRef.current?.contains(event.target as Node)) {
                setIsOpen(false);
            }
        }

        function handleKeyDown(event: KeyboardEvent): void {
            if (event.key === 'Escape') {
                setIsOpen(false);
            }
        }

        document.addEventListener('pointerdown', handlePointerDown);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('pointerdown', handlePointerDown);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [isOpen]);

    function openOnMouseHover(event: PointerEvent): void {
        if (event.pointerType === 'mouse') {
            setIsOpen(true);
        }
    }

    function closeOnMouseLeave(event: PointerEvent): void {
        if (event.pointerType === 'mouse') {
            setIsOpen(false);
        }
    }

    return (
        <div
            ref={containerRef}
            className="relative"
            onPointerEnter={openOnMouseHover}
            onPointerLeave={closeOnMouseLeave}
        >
            <button
                type="button"
                aria-label={`Cart, ${itemCount} ${itemCount === 1 ? 'item' : 'items'}`}
                aria-expanded={isOpen}
                aria-controls="mini-cart"
                onClick={() => setIsOpen((open) => !open)}
                className="relative flex size-11 items-center justify-center rounded-xl bg-linear-to-b from-accent-500 to-accent-700 text-white shadow-[0_4px_0_0_var(--color-accent-700),0_8px_14px_-4px_rgb(0_0_0/0.35),inset_0_1px_0_0_rgb(255_255_255/0.35)] transition-[transform,box-shadow] hover:-translate-y-0.5 active:translate-y-0.5 active:shadow-[0_1px_0_0_var(--color-accent-700),inset_0_1px_0_0_rgb(255_255_255/0.35)]"
            >
                <Icon
                    name="cart"
                    size={22}
                    className="drop-shadow-[0_2px_1px_rgb(0_0_0/0.3)]"
                />
                {itemCount > 0 && (
                    <span className="absolute -top-2 -right-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-neutral-900 px-1 text-xs font-semibold text-white ring-2 ring-white">
                        {itemCount}
                    </span>
                )}
            </button>

            {isOpen && (
                <CartPopover
                    lines={lines}
                    subtotal={subtotal}
                    onRemove={remove}
                    onClear={clear}
                    onNavigate={() => setIsOpen(false)}
                />
            )}
        </div>
    );
}
