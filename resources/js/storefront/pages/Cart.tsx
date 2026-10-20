import { Head } from '@inertiajs/react';
import { DEFAULT_DELIVERY_CHARGE } from '@/storefront/config/site';
import { CartLineList, EmptyCart, useCart } from '@/storefront/features/cart';
import OrderForm from '@/storefront/features/order/components/OrderForm/OrderForm';

export default function Cart() {
    const { lines, subtotal, itemCount, setQuantity, remove } = useCart();

    return (
        <>
            <Head title="Cart">
                <meta name="robots" content="noindex" />
            </Head>

            <section className="mx-auto max-w-7xl px-4 py-8 sm:px-6">
                <h1 className="text-2xl font-bold tracking-tight text-neutral-900">
                    Your cart
                    {itemCount > 0 && (
                        <span className="ml-2 text-base font-medium text-neutral-500">
                            ({itemCount} {itemCount === 1 ? 'item' : 'items'})
                        </span>
                    )}
                </h1>

                {lines.length === 0 ? (
                    <div className="mt-6">
                        <EmptyCart />
                    </div>
                ) : (
                    <div className="mt-6 grid items-start gap-6 lg:grid-cols-[1.4fr_1fr]">
                        <CartLineList
                            lines={lines}
                            onQuantityChange={setQuantity}
                            onRemove={remove}
                        />
                        <div className="lg:sticky lg:top-24">
                            <OrderForm
                                source="cart"
                                subtotal={subtotal}
                                deliveryCharge={DEFAULT_DELIVERY_CHARGE}
                            />
                        </div>
                    </div>
                )}
            </section>
        </>
    );
}
