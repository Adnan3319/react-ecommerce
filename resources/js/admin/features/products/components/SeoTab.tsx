import { usePage } from '@inertiajs/react';
import Input from '@admin/components/form/input/InputField';
import TextArea from '@admin/components/form/input/TextArea';
import Label from '@admin/components/form/Label';
import { LIMITS } from '../constants';
import type { FormApi } from '../types';

export default function SeoTab({ api }: { api: FormApi }) {
    const { data, errors, set } = api;
    const { name: appName } = usePage<{ name: string }>().props;

    const previewTitle =
        data.meta_title || `${data.name || 'Product name'} | ${appName}`;
    const previewDescription =
        data.meta_description ||
        data.short_description ||
        'No description yet.';

    return (
        <section className="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
            <div>
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                    SEO
                </h2>
                <p className="text-sm text-gray-500">
                    Optional. Empty fields fall back to the product name and
                    short description.
                </p>
            </div>

            <div>
                <Label htmlFor="meta-title">Meta title</Label>
                <Input
                    id="meta-title"
                    value={data.meta_title}
                    placeholder={`${data.name || 'Product name'} | ${appName}`}
                    onChange={(event) => set('meta_title', event.target.value)}
                    error={Boolean(errors.meta_title)}
                    hint={errors.meta_title}
                />
                <p className="mt-1.5 text-xs text-gray-500">
                    {data.meta_title.length}/{LIMITS.metaTitle}
                </p>
            </div>

            <div>
                <Label>Meta description</Label>
                <TextArea
                    rows={3}
                    value={data.meta_description}
                    placeholder={data.short_description}
                    onChange={(value) => set('meta_description', value)}
                    error={Boolean(errors.meta_description)}
                    hint={errors.meta_description}
                />
                <p className="mt-1.5 text-xs text-gray-500">
                    {data.meta_description.length}/{LIMITS.metaDescription}
                </p>
            </div>

            <div>
                <Label>Google preview</Label>
                <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/5">
                    <p className="text-success-700 text-xs">
                        {appName} › products › {data.slug || 'product-slug'}
                    </p>
                    <p className="text-lg text-blue-700 dark:text-blue-400">
                        {previewTitle}
                    </p>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        {previewDescription}
                    </p>
                </div>
            </div>
        </section>
    );
}
