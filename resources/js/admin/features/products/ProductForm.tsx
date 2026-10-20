import { Link } from '@inertiajs/react';
import { useState } from 'react';
import Button from '@admin/components/ui/button/Button';
import BasicInfoTab from './components/BasicInfoTab';
import ImagesTab from './components/ImagesTab';
import PriceStockTab from './components/PriceStockTab';
import SeoTab from './components/SeoTab';
import TabNav from './components/TabNav';
import { TABS } from './constants';
import type { TabId } from './constants';
import { tabsWithErrors } from './form-state';
import type { ProductFormProps } from './types';
import { useProductForm } from './useProductForm';
import { index } from '@/routes/admin/products';

type Navigation = {
    tab: TabId;
    visited: TabId[];
    onTabChange: (tab: TabId) => void;
};

/**
 * Create and edit share this. The body remounts after a successful edit, so the form restarts from the
 * fresh server data (new ids, stock) while the tab the admin was on stays.
 */
export default function ProductForm(props: ProductFormProps) {
    const [tab, setTab] = useState<TabId>('basic');
    const [visited, setVisited] = useState<TabId[]>(['basic']);
    const [version, setVersion] = useState(0);

    const onTabChange = (next: TabId) => {
        setTab(next);
        setVisited((current) =>
            current.includes(next) ? current : [...current, next],
        );
    };

    return (
        <ProductFormBody
            key={version}
            {...props}
            tab={tab}
            visited={visited}
            onTabChange={onTabChange}
            onSaved={() => setVersion((current) => current + 1)}
        />
    );
}

function ProductFormBody({
    categoryTree,
    galleryMax,
    maxVariantHeads,
    maxVariants,
    product,
    tab,
    visited,
    onTabChange,
    onSaved,
}: ProductFormProps & Navigation & { onSaved: () => void }) {
    const { api, submit, processing, isEdit } = useProductForm(product, {
        onErrorTabs: (tabs) => tabs[0] && onTabChange(tabs[0]),
        onSaved,
    });

    const position = TABS.findIndex((candidate) => candidate.id === tab);
    const previous = TABS[position - 1];
    const next = TABS[position + 1];
    const errorTabs = tabsWithErrors(api.errors);
    const title = isEdit ? 'Edit product' : 'Add product';

    return (
        <div className="w-full">
            <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p className="text-sm text-gray-500">
                        <Link
                            href={index().url}
                            className="hover:text-brand-500"
                        >
                            Products
                        </Link>{' '}
                        / {title}
                    </p>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">
                        {title}
                    </h1>
                </div>
                <div className="flex gap-3">
                    <Link
                        href={index().url}
                        className="inline-flex items-center rounded-lg bg-white px-5 py-3.5 text-sm font-medium text-gray-700 ring-1 ring-gray-300 ring-inset hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700"
                    >
                        Cancel
                    </Link>
                    <Button onClick={submit} disabled={processing}>
                        {processing ? 'Saving...' : 'Save product'}
                    </Button>
                </div>
            </div>

            <TabNav
                tab={tab}
                visited={visited}
                errorTabs={errorTabs}
                onSelect={onTabChange}
            />

            <div className="mt-6 space-y-6">
                {tab === 'basic' && (
                    <BasicInfoTab api={api} categoryTree={categoryTree} />
                )}
                {tab === 'images' && (
                    <ImagesTab api={api} galleryMax={galleryMax} />
                )}
                {tab === 'price' && (
                    <PriceStockTab
                        api={api}
                        maxVariantHeads={maxVariantHeads}
                        maxVariants={maxVariants}
                    />
                )}
                {tab === 'seo' && <SeoTab api={api} />}
            </div>

            <div className="mt-6 flex items-center justify-between">
                {previous ? (
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => onTabChange(previous.id)}
                    >
                        ← {previous.label}
                    </Button>
                ) : (
                    <span className="text-sm text-gray-500">
                        Step 1 of {TABS.length}
                    </span>
                )}
                {next ? (
                    <Button size="sm" onClick={() => onTabChange(next.id)}>
                        Next: {next.label} →
                    </Button>
                ) : (
                    <Button size="sm" onClick={submit} disabled={processing}>
                        {processing ? 'Saving...' : 'Save product'}
                    </Button>
                )}
            </div>
        </div>
    );
}
