import Input from '@admin/components/form/input/InputField';
import Label from '@admin/components/form/Label';
import Switch from '@admin/components/form/switch/Switch';
import Badge from '@admin/components/ui/badge/Badge';
import Button from '@admin/components/ui/button/Button';
import { discountPercent } from '../form-state';
import type { FormApi } from '../types';
import { useVariantMatrix } from '../useVariantMatrix';
import { missingKeys } from '../variant-matrix';
import BulkApply from './BulkApply';
import FieldError from './FieldError';
import VariantHeads from './VariantHeads';
import VariantTable from './VariantTable';

type PriceStockTabProps = {
    api: FormApi;
    maxVariantHeads: number;
    maxVariants: number;
};

export default function PriceStockTab({
    api,
    maxVariantHeads,
    maxVariants,
}: PriceStockTabProps) {
    const { data, errors, set } = api;
    const matrix = useVariantMatrix(api, {
        maxHeads: maxVariantHeads,
        maxVariants,
    });
    const discount = discountPercent(data.price, data.sale_price);
    const missing = missingKeys(data.heads, data.rows).length;

    return (
        <section className="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
            <div>
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                    Price & stock
                </h2>
                <p className="text-sm text-gray-500">
                    Simple product by default. Turn on variants for size, color,
                    capacity...
                </p>
            </div>

            <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/5">
                <Switch
                    label="This product has variants"
                    defaultChecked={data.has_variants}
                    onChange={matrix.setHasVariants}
                />
                <FieldError message={errors.has_variants} />
            </div>

            {data.has_variants ? (
                <>
                    <VariantHeads
                        api={api}
                        matrix={matrix}
                        maxVariantHeads={maxVariantHeads}
                        maxVariants={maxVariants}
                    />

                    {data.rows.length > 0 && (
                        <BulkApply onApply={matrix.applyToAll} />
                    )}

                    {missing > 0 && (
                        <div className="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                            {missing} combination
                            {missing === 1 ? ' is' : 's are'} not used.
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={matrix.addMissing}
                            >
                                Add missing combinations
                            </Button>
                        </div>
                    )}

                    {data.rows.length > 0 ? (
                        <VariantTable api={api} matrix={matrix} />
                    ) : (
                        <p className="text-sm text-gray-500">
                            Add values to the variant heads to generate the
                            variants.
                        </p>
                    )}
                    <FieldError message={errors.variants} />
                    <p className="text-xs text-gray-500">
                        Rows are generated from the head values. Click an image
                        to pick from the gallery.
                    </p>
                </>
            ) : (
                <div className="grid gap-4 md:grid-cols-4">
                    <div>
                        <Label htmlFor="product-price">Price (৳) *</Label>
                        <Input
                            id="product-price"
                            inputMode="decimal"
                            value={data.price}
                            onChange={(event) =>
                                set('price', event.target.value)
                            }
                            error={Boolean(errors.price)}
                            hint={errors.price}
                        />
                    </div>
                    <div>
                        <Label htmlFor="product-sale-price">
                            Sale price (৳)
                        </Label>
                        <Input
                            id="product-sale-price"
                            inputMode="decimal"
                            value={data.sale_price}
                            onChange={(event) =>
                                set('sale_price', event.target.value)
                            }
                            error={Boolean(errors.sale_price)}
                            hint={errors.sale_price}
                        />
                        {discount !== null && (
                            <Badge size="sm" color="success" className="mt-1">
                                -{discount}%
                            </Badge>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="product-stock">Stock *</Label>
                        <Input
                            id="product-stock"
                            inputMode="numeric"
                            value={data.stock}
                            onChange={(event) =>
                                set('stock', event.target.value)
                            }
                            error={Boolean(errors.stock)}
                            hint={errors.stock}
                        />
                    </div>
                    <div>
                        <Label htmlFor="product-sku">SKU</Label>
                        <Input
                            id="product-sku"
                            value={data.sku}
                            placeholder="Auto"
                            onChange={(event) => set('sku', event.target.value)}
                            error={Boolean(errors.sku)}
                            hint={errors.sku}
                        />
                    </div>
                </div>
            )}
        </section>
    );
}
