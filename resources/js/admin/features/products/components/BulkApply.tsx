import { useState } from 'react';
import Input from '@admin/components/form/input/InputField';
import Button from '@admin/components/ui/button/Button';
import type { BulkValues } from '../useVariantMatrix';

type BulkApplyProps = {
    onApply: (values: BulkValues) => void;
};

const EMPTY: BulkValues = { price: '', sale_price: '', stock: '' };

/** "Set for all": fills the typed price / sale price / stock into every row. */
export default function BulkApply({ onApply }: BulkApplyProps) {
    const [values, setValues] = useState<BulkValues>(EMPTY);

    const field = (key: keyof BulkValues, placeholder: string) => (
        <div className="w-32">
            <Input
                inputMode="decimal"
                value={values[key]}
                placeholder={placeholder}
                aria-label={`Set ${placeholder.toLowerCase()} for all`}
                onChange={(event) =>
                    setValues((current) => ({
                        ...current,
                        [key]: event.target.value,
                    }))
                }
            />
        </div>
    );

    return (
        <div className="flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-white/5">
            <span className="text-sm font-medium text-gray-700 dark:text-gray-400">
                Set for all:
            </span>
            {field('price', 'Price')}
            {field('sale_price', 'Sale')}
            {field('stock', 'Stock')}
            <Button
                variant="outline"
                size="sm"
                onClick={() => {
                    onApply(values);
                    setValues(EMPTY);
                }}
            >
                Apply to all
            </Button>
        </div>
    );
}
