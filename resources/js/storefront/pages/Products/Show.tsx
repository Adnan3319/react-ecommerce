import { Head } from '@inertiajs/react';
import { home } from '@/routes';
import { formatMoney } from '@/shared/lib';
import { Badge } from '@/storefront/components/ui';
import { Breadcrumbs } from '@/storefront/components/Breadcrumbs';
import {
    DEFAULT_DELIVERY_CHARGE,
    findCategory,
} from '@/storefront/config/site';
import { AddToCartButton } from '@/storefront/features/cart';
import { MobileOrderBar, OrderForm } from '@/storefront/features/order';
import {
    ProductGallery,
    findSampleProduct,
} from '@/storefront/features/product';
import { categoryHref } from '@/storefront/lib/category-href';

/** Integer minor units. Placeholder: set your real Outside Dhaka charge. */
const OUTSIDE_DHAKA_DELIVERY_CHARGE = 12000;

type ShowProps = {
    slug: string;
};

export default function Show({ slug }: ShowProps) {
    const product = findSampleProduct(slug);

    if (!product) {
        return (
            <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6">
                <Head title="Product not found">
                    <meta name="robots" content="noindex" />
                </Head>
                <h1 className="text-2xl font-bold">Product not found</h1>
            </section>
        );
    }

    const category = findCategory(product.categorySlug);

    return (
        <>
            <Head title={product.name}>
                <meta name="description" content={product.description} />
            </Head>

            <div className="mx-auto max-w-7xl px-4 pt-6 pb-24 sm:px-6 md:pb-12">
                <Breadcrumbs
                    items={[
                        { label: 'Home', href: home() },
                        ...(category
                            ? [
                                  {
                                      label: category.label,
                                      href: categoryHref(category.slug),
                                  },
                              ]
                            : []),
                        { label: product.name },
                    ]}
                />

                <div className="mt-6 grid gap-8 lg:grid-cols-[1fr_380px] lg:items-start lg:gap-10">
                    {/* Left: main image + product info, thumbnails below */}
                    <ProductGallery
                        name={product.name}
                        images={product.images}
                        info={
                            <header className="flex flex-col gap-3">
                                <Badge tone="success" className="w-fit">
                                    In stock
                                </Badge>
                                <h1 className="text-3xl font-extrabold tracking-tight text-neutral-900 xl:text-4xl">
                                    {product.name}
                                </h1>
                                <p className="text-xs leading-relaxed text-neutral-400">
                                    {product.description}
                                </p>

                                <div className="mt-3">
                                    <p className="text-xs text-neutral-500">
                                        Total price:
                                    </p>
                                    <p className="text-4xl font-extrabold tracking-tight text-neutral-900">
                                        {formatMoney(product.price)}
                                    </p>
                                </div>

                                <AddToCartButton
                                    product={product}
                                    variant="soft"
                                    size="lg"
                                    className="hidden w-fit md:inline-flex"
                                />
                            </header>
                        }
                    />

                    {/* Right: the order form sits where the Overview box is */}
                    <OrderForm
                        source="buy_now"
                        productId={product.id}
                        productName={product.name}
                        unitPrice={product.price}
                        deliveryCharge={DEFAULT_DELIVERY_CHARGE}
                        outsideDhakaDeliveryCharge={OUTSIDE_DHAKA_DELIVERY_CHARGE}
                    />
                </div>
            </div>

            <MobileOrderBar
                price={product.price}
                addToCart={
                    <AddToCartButton
                        product={product}
                        variant="soft"
                        label="Add"
                    />
                }
            />
        </>
    );
}
