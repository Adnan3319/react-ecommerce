import { useState } from 'react';
import { home } from '@/routes';
import { Badge, QuantityStepper } from '@/storefront/components/ui';
import AddedToast from '@/storefront/components/AddedToast';
import AddToCartButton from '@/storefront/features/cart/AddToCartButton';
import { useAddToCart } from '@/storefront/hooks/useAddToCart';
import { Breadcrumbs } from '@/storefront/components/Breadcrumbs';
import MobileOrderBar from '@/storefront/features/order/MobileOrderBar';
import OrderForm from '@/storefront/features/order/OrderForm';
import { categoryHref } from '@/storefront/lib/category-href';
import PriceTag from './PriceTag';
import ProductGallery from './ProductGallery';
import StockNotice from './StockNotice';
import type { ProductPageProps } from './types';
import { useVariantSelection } from '@/storefront/components/useVariantSelection';
import VariantPicker from '@/storefront/components/VariantPicker';

type ProductViewProps = Omit<ProductPageProps, 'seo'>;

/** The product page body: gallery, price, variants, Order Now form and description. */
export default function ProductView({
    product,
    category,
    deliveryZones,
    maxQty,
}: ProductViewProps) {
    const { selected, variant, isAvailable, select } = useVariantSelection(
        product.heads,
        product.variants,
    );
    const [requested, setRequested] = useState(1);

    const outOfStock = !variant || variant.stock <= 0;
    const maxQuantity = Math.max(1, Math.min(variant?.stock ?? 1, maxQty));
    const quantity = Math.min(requested, maxQuantity);
    const unitPrice = variant?.effective_price ?? 0;
    const cart = useAddToCart(variant?.id ?? null, quantity);
    const lowestCharge = Math.min(...deliveryZones.map((zone) => zone.charge));

    return (
        <>
            <div className="mx-auto max-w-7xl px-4 pt-6 pb-36 sm:px-6 md:pb-12">
                <Breadcrumbs
                    items={[
                        { label: 'Home', href: home() },
                        ...category.trail.map((crumb) => ({
                            label: crumb.label,
                            href: categoryHref(crumb.slug),
                        })),
                        { label: product.name },
                    ]}
                />

                <div className="mt-6 grid gap-8 lg:grid-cols-2 lg:items-start lg:gap-12">
                    <ProductGallery
                        key={variant?.image_id ?? 'default'}
                        name={product.name}
                        images={product.images}
                        initialImageId={variant?.image_id ?? null}
                        badge={
                            variant?.discount_percent ? (
                                <Badge
                                    tone="danger"
                                    className="px-3 py-1 text-sm font-semibold"
                                >
                                    -{variant.discount_percent}%
                                </Badge>
                            ) : null
                        }
                    />

                    <div className="flex flex-col gap-5">
                        <h1 className="text-3xl font-extrabold tracking-tight text-neutral-900 xl:text-4xl">
                            {product.name}
                        </h1>

                        {variant && (
                            <PriceTag
                                price={variant.price}
                                salePrice={variant.sale_price}
                            />
                        )}
                        {variant && (
                            <StockNotice
                                state={variant.stock_state}
                                stock={variant.stock}
                            />
                        )}

                        {product.short_description && (
                            <p className="text-neutral-600">
                                {product.short_description}
                            </p>
                        )}

                        {product.heads.length > 0 && (
                            <VariantPicker
                                heads={product.heads}
                                selected={selected}
                                isAvailable={isAvailable}
                                onSelect={select}
                            />
                        )}

                        {!outOfStock && (
                            <QuantityStepper
                                label="Quantity"
                                value={quantity}
                                min={1}
                                max={maxQuantity}
                                onChange={setRequested}
                            />
                        )}

                        <AddToCartButton
                            onAdd={cart.add}
                            processing={cart.processing}
                            disabled={outOfStock}
                            error={cart.error}
                        />

                        {variant && (
                            <OrderForm
                                zones={deliveryZones}
                                mode={{
                                    source: 'buy_now',
                                    variantId: variant.id,
                                    quantity,
                                    unitPrice,
                                    outOfStock,
                                }}
                            />
                        )}
                    </div>
                </div>

                {product.description && (
                    <section className="mt-12 max-w-3xl">
                        <h2 className="text-2xl font-bold tracking-tight text-neutral-900">
                            Description
                        </h2>
                        <p className="mt-3 leading-relaxed whitespace-pre-line text-neutral-700">
                            {product.description}
                        </p>
                    </section>
                )}
            </div>

            <MobileOrderBar
                total={unitPrice * quantity + lowestCharge}
                disabled={outOfStock}
                onAddToCart={cart.add}
                addingToCart={cart.processing}
            />
            {cart.added && <AddedToast onDismiss={cart.dismiss} />}
        </>
    );
}
