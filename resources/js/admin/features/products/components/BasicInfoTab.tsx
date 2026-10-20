import Input from '@admin/components/form/input/InputField';
import TextArea from '@admin/components/form/input/TextArea';
import Label from '@admin/components/form/Label';
import Switch from '@admin/components/form/switch/Switch';
import { STATUS_ACTIVE, STATUS_INACTIVE } from '@/shared/constants/status';
import { LIMITS, PRODUCT_PATH_PREFIX } from '../constants';
import { slugify } from '../form-state';
import type { CategoryNode, FormApi } from '../types';
import CategoryPicker from './CategoryPicker';

type BasicInfoTabProps = {
    api: FormApi;
    categoryTree: CategoryNode[];
};

export default function BasicInfoTab({ api, categoryTree }: BasicInfoTabProps) {
    const { data, errors, set, update } = api;

    const changeName = (name: string) =>
        update((previous) => ({ ...previous, name, slug: previous.slugTouched ? previous.slug : slugify(name) }));

    return (
        <>
            <section className="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                <div>
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">Basic information</h2>
                    <p className="text-sm text-gray-500">Name and description customers will see.</p>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <Label htmlFor="product-name">Product name *</Label>
                        <Input
                            id="product-name"
                            value={data.name}
                            onChange={(event) => changeName(event.target.value)}
                            error={Boolean(errors.name)}
                            hint={errors.name}
                        />
                    </div>
                    <div>
                        <Label htmlFor="product-slug">URL (slug)</Label>
                        <div className="flex">
                            <span className="inline-flex h-11 items-center rounded-s-lg border border-e-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 dark:border-gray-700 dark:bg-white/5">
                                {PRODUCT_PATH_PREFIX}
                            </span>
                            <div className="flex-1">
                                <Input
                                    id="product-slug"
                                    value={data.slug}
                                    className="rounded-s-none"
                                    onChange={(event) =>
                                        update((previous) => ({
                                            ...previous,
                                            slug: event.target.value,
                                            slugTouched: true,
                                        }))
                                    }
                                    error={Boolean(errors.slug)}
                                    hint={errors.slug}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <Label>Short description</Label>
                    <TextArea
                        rows={3}
                        value={data.short_description}
                        onChange={(value) => set('short_description', value)}
                        placeholder="Shown on product cards"
                        error={Boolean(errors.short_description)}
                        hint={errors.short_description}
                    />
                    <p className="mt-1.5 text-xs text-gray-500">
                        Shown on product cards · {data.short_description.length}/{LIMITS.shortDescription}
                    </p>
                </div>

                <div>
                    <Label>Description</Label>
                    <TextArea
                        rows={6}
                        value={data.description}
                        onChange={(value) => set('description', value)}
                        placeholder="Fabric, fit, care instructions... (line breaks are kept)"
                        error={Boolean(errors.description)}
                        hint={errors.description}
                    />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/5">
                        <Switch
                            label="Active — visible in the store"
                            defaultChecked={data.status === STATUS_ACTIVE}
                            onChange={(checked) => set('status', checked ? STATUS_ACTIVE : STATUS_INACTIVE)}
                        />
                    </div>
                    <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/5">
                        <Switch
                            label="Featured — show on the home page"
                            defaultChecked={data.is_featured}
                            onChange={(checked) => set('is_featured', checked)}
                        />
                    </div>
                </div>
            </section>

            <section className="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                <div>
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">Category *</h2>
                    <p className="text-sm text-gray-500">Pick one. Sub-categories are shown under their parent.</p>
                </div>
                <CategoryPicker
                    tree={categoryTree}
                    value={data.category_id}
                    error={errors.category_id}
                    onChange={(categoryId) => set('category_id', categoryId)}
                />
            </section>
        </>
    );
}
