import Input from '@admin/components/form/input/InputField';
import Label from '@admin/components/form/Label';
import Button from '@admin/components/ui/button/Button';
import type { FormApi } from '../types';
import type { useVariantMatrix } from '../useVariantMatrix';
import ChipInput from './ChipInput';
import FieldError from './FieldError';

type VariantHeadsProps = {
    api: FormApi;
    matrix: ReturnType<typeof useVariantMatrix>;
    maxVariantHeads: number;
    maxVariants: number;
};

/** Error messages of `heads.<index>.values.<n>.<field>` keyed by value index. */
function valueErrors(errors: FormApi['errors'], headIndex: number): Partial<Record<number, string>> {
    const prefix = `heads.${headIndex}.values.`;
    const found: Partial<Record<number, string>> = {};

    for (const [key, message] of Object.entries(errors)) {
        if (key.startsWith(prefix)) {
            found[Number(key.slice(prefix.length).split('.')[0])] = message;
        }
    }

    return found;
}

export default function VariantHeads({ api, matrix, maxVariantHeads, maxVariants }: VariantHeadsProps) {
    const { data, errors } = api;

    return (
        <div className="space-y-4">
            {data.heads.map((head, index) => (
                <div
                    key={head.key}
                    className="grid gap-4 rounded-lg border border-gray-200 p-4 md:grid-cols-[14rem_1fr_auto] dark:border-gray-800"
                >
                    <div>
                        <Label htmlFor={`head-name-${head.key}`}>Variant head {index + 1} name</Label>
                        <Input
                            id={`head-name-${head.key}`}
                            value={head.name}
                            placeholder={index === 0 ? 'Size' : 'Color'}
                            onChange={(event) => matrix.renameHead(head.key, event.target.value)}
                            error={Boolean(errors[`heads.${index}.name`])}
                            hint={errors[`heads.${index}.name`]}
                        />
                    </div>
                    <div>
                        <Label>Values</Label>
                        <ChipInput
                            chips={head.values}
                            maxVariants={maxVariants}
                            label={head.name || `Head ${index + 1}`}
                            errors={valueErrors(errors, index)}
                            onAdd={(text) => matrix.addChip(head.key, text)}
                            onRename={(chipKey, text) => matrix.renameChip(head.key, chipKey, text)}
                            onRemove={(chipKey) => matrix.removeChip(head.key, chipKey)}
                        />
                        <FieldError message={errors[`heads.${index}.values`]} />
                        <FieldError message={errors[`heads.${index}.id`]} />
                    </div>
                    <div className="flex items-start md:pt-7">
                        <button
                            type="button"
                            aria-label={`Remove variant head ${index + 1}`}
                            onClick={() => matrix.removeHead(head.key)}
                            className="text-sm text-error-500 hover:text-error-600"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            ))}

            <FieldError message={errors.heads} />

            <div className="flex items-center gap-3">
                <Button variant="outline" size="sm" onClick={matrix.addHead} disabled={!matrix.canAddHead}>
                    + Add variant head
                </Button>
                <span className="text-xs text-gray-500">Up to {maxVariantHeads} variant heads</span>
            </div>
        </div>
    );
}
