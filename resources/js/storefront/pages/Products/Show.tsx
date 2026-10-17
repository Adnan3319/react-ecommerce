import { Head } from '@inertiajs/react';
import { home } from '@/routes';
import { formatMoney } from '@/shared/lib';
import { Badge } from '@/shared/ui';
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

                <div className="mt-6 grid gap-8 lg:grid-cols-[1.1fr_1fr] lg:gap-12">
                    <ProductGallery
                        name={product.name}
                        images={product.images}
                    />

                    <div className="flex flex-col gap-6">
                        <header className="flex flex-col gap-3">
                            <Badge tone="success" className="w-fit">
                                In stock
                            </Badge>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl">
                                {product.name}
                            </h1>
                            <p className="text-3xl font-bold text-accent-600">
                                {formatMoney(product.price)}
                            </p>
                            <p className="text-neutral-600">
                                {product.description}
                            </p>
                        </header>

                        <AddToCartButton
                            product={product}
                            size="lg"
                            fullWidth
                            className="hidden md:inline-flex"
                        />

                        <OrderForm
                            source="buy_now"
                            productId={product.id}
                            productName={product.name}
                            unitPrice={product.price}
                            deliveryCharge={DEFAULT_DELIVERY_CHARGE}
                        />
                    </div>
                </div>
            </div>

            <MobileOrderBar
                price={product.price}
                addToCart={<AddToCartButton product={product} label="Add" />}
            />
        </>
    );
}
