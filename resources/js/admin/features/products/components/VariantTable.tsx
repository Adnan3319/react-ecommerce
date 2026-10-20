import { useState } from 'react';
import Input from '@admin/components/form/input/InputField';
import Switch from '@admin/components/form/switch/Switch';
import Badge from '@admin/components/ui/badge/Badge';
import { STATUS_ACTIVE, STATUS_INACTIVE } from '@/shared/constants/status';
import { MAIN_IMAGE_KEY } from '../constants';
import { discountPercent } from '../form-state';
import type { FormApi, ImageItem, VariantRow } from '../types';
import type { useVariantMatrix } from '../useVariantMatrix';
import { rowLabel } from '../variant-matrix';
import FieldError from './FieldError';

type VariantTableProps = {
    api: FormApi;
    matrix: ReturnType<typeof useVariantMatrix>;
};

type PickerProps = {
    selected: ImageItem | undefined;
    images: ImageItem[];
    label: string;
    onPick: (imageKey: string | null) => void;
};

/** Thumbnail button that opens the product's own images (main + gallery) to pick from. */
function ImagePicker({ selected, images, label, onPick }: PickerProps) {
    const [open, setOpen] = useState(false);

    return (
        <div className="relative">
            <button
                type="button"
                aria-label={`Pick image for ${label}`}
                aria-expanded={open}
                onClick={() => setOpen((current) => !current)}
                className="flex h-12 w-12 items-center justify-center overflow-hidden rounded-md border border-gray-200 bg-gray-50 text-[10px] text-gray-400 dark:border-gray-700 dark:bg-white/5"
            >
                {selected ? <img src={selected.url} alt="" className="h-full w-full object-cover" /> : 'Pick'}
            </button>
            {open && (
                <div className="absolute start-0 top-14 z-20 flex w-52 flex-wrap gap-2 rounded-lg border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-700 dark:bg-gray-900">
                    {images.map((image) => (
                        <button
                            key={image.key}
                            type="button"
                            aria-label={image.key === MAIN_IMAGE_KEY ? 'Main image' : 'Gallery image'}
                            onClick={() => {
                                onPick(image.key);
                                setOpen(false);
                            }}
                            className="h-12 w-12 overflow-hidden rounded-md border border-gray-200 hover:border-brand-500"
                        >
                            <img src={image.url} alt="" className="h-full w-full object-cover" />
                        </button>
                    ))}
                    <button
                        type="button"
                        onClick={() => {
                            onPick(null);
                            setOpen(false);
                        }}
                        className="h-12 rounded-md px-2 text-xs text-gray-500 hover:text-error-500"
                    >
                        None
                    </button>
                </div>
            )}
        </div>
    );
}

export default function VariantTable({ api, matrix }: VariantTableProps) {
    const { data, errors } = api;
    const images = [...(data.main ? [data.main] : []), ...data.gallery];

    const cell = (row: VariantRow, index: number, field: 'sku' | 'price' | 'sale_price' | 'stock') => {
        const message = errors[`variants.${index}.${field}`];

        return (
            <Input
                inputMode={field === 'sku' ? 'text' : 'decimal'}
                value={row[field]}
                placeholder={field === 'sku' ? 'Auto' : field === 'sale_price' ? '—' : undefined}
                aria-label={`${field.replace('_', ' ')} of ${rowLabel(data.heads, row)}`}
                onChange={(event) => matrix.updateRow(row.key, { [field]: event.target.value })}
                error={Boolean(message)}
                hint={message}
            />
        );
    };

    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[56rem] text-sm">
                <thead>
                    <tr className="bg-gray-50 text-left text-xs font-medium text-gray-500 dark:bg-white/5">
                        <th className="px-3 py-2">Image</th>
                        <th className="px-3 py-2">Variant</th>
                        <th className="px-3 py-2">SKU</th>
                        <th className="px-3 py-2">Price (৳)</th>
                        <th className="px-3 py-2">Sale price (৳)</th>
                        <th className="px-3 py-2">Stock</th>
                        <th className="px-3 py-2">Active</th>
                        <th className="px-3 py-2" />
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
                    {data.rows.map((row, index) => {
                        const label = rowLabel(data.heads, row);
                        const discount = discountPercent(row.price, row.sale_price);

                        return (
                            <tr key={row.key} className="align-top">
                                <td className="px-3 py-3">
                                    <ImagePicker
                                        label={label}
                                        images={images}
                                        selected={images.find((image) => image.key === row.imageKey)}
                                        onPick={(imageKey) => matrix.updateRow(row.key, { imageKey })}
                                    />
                                    <FieldError message={errors[`variants.${index}.image_ref`]} />
                                </td>
                                <td className="px-3 py-3 font-medium text-gray-900 dark:text-white/90">
                                    {label}
                                    <FieldError message={errors[`variants.${index}.values`]} />
                                    <FieldError message={errors[`variants.${index}.id`]} />
                                </td>
                                <td className="w-40 px-3 py-3">{cell(row, index, 'sku')}</td>
                                <td className="w-32 px-3 py-3">{cell(row, index, 'price')}</td>
                                <td className="w-40 px-3 py-3">
                                    {cell(row, index, 'sale_price')}
                                    {discount !== null && (
                                        <Badge size="sm" color="success" className="mt-1">
                                            -{discount}%
                                        </Badge>
                                    )}
                                </td>
                                <td className="w-28 px-3 py-3">
                                    {cell(row, index, 'stock')}
                                    {Number(row.stock) === 0 && (
                                        <Badge size="sm" color="error" className="mt-1">
                                            Out of stock
                                        </Badge>
                                    )}
                                </td>
                                <td className="px-3 py-3">
                                    <Switch
                                        label=""
                                        defaultChecked={row.status === STATUS_ACTIVE}
                                        onChange={(checked) =>
                                            matrix.updateRow(row.key, { status: checked ? STATUS_ACTIVE : STATUS_INACTIVE })
                                        }
                                    />
                                </td>
                                <td className="px-3 py-3">
                                    <button
                                        type="button"
                                        aria-label={`Remove ${label}`}
                                        onClick={() => matrix.removeRow(row.key)}
                                        className="text-lg text-gray-400 hover:text-error-500"
                                    >
                                        ×
                                    </button>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
