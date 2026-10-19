import { router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@admin/components/common/ConfirmDialog';
import Button from '@admin/components/ui/button/Button';
import Pagination from '@admin/components/ui/pagination/Pagination';
import { destroy, update } from '@/routes/admin/categories';
import CategoryFilters from './CategoryFilters';
import CategoryFormModal from './CategoryFormModal';
import CategoryTable from './CategoryTable';
import { STATUS_ACTIVE, STATUS_INACTIVE } from './constants';
import type { Category, CategoryIndexProps } from './types';
import { useCategoryFilters } from './useCategoryFilters';

type FormTarget = { category: Category | null };

export default function CategoryManager({ categories, filters, statuses }: CategoryIndexProps) {
    const [formTarget, setFormTarget] = useState<FormTarget | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Category | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState<number | null>(null);

    const { search, setSearch, status, setStatus, goToPage, processing } =
        useCategoryFilters(filters);

    const { meta } = categories;

    const toggleStatus = (category: Category) => {
        router.put(
            update(category.id).url,
            { status: category.status === STATUS_ACTIVE ? STATUS_INACTIVE : STATUS_ACTIVE },
            {
                preserveScroll: true,
                onStart: () => setTogglingId(category.id),
                onFinish: () => setTogglingId(null),
            },
        );
    };

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        router.delete(destroy(deleteTarget.id).url, {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    };

    return (
        <div className="w-full">
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">Categories</h1>
                <Button size="sm" onClick={() => setFormTarget({ category: null })}>
                    Add category
                </Button>
            </div>

            <CategoryFilters
                search={search}
                onSearchChange={setSearch}
                status={status}
                onStatusChange={setStatus}
                statuses={statuses}
            />

            <CategoryTable
                categories={categories.data}
                loading={processing}
                busyId={togglingId}
                onEdit={(category) => setFormTarget({ category })}
                onDelete={setDeleteTarget}
                onToggleStatus={toggleStatus}
            />

            <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm text-gray-500">
                    {meta.total > 0
                        ? `Showing ${meta.from}–${meta.to} of ${meta.total}`
                        : 'No results'}
                </p>
                <Pagination
                    currentPage={meta.current_page}
                    lastPage={meta.last_page}
                    onPageChange={goToPage}
                />
            </div>

            {formTarget && (
                <CategoryFormModal
                    category={formTarget.category}
                    statuses={statuses}
                    defaultSortOrder={meta.total + 1}
                    onClose={() => setFormTarget(null)}
                />
            )}

            <ConfirmDialog
                isOpen={deleteTarget !== null}
                title="Delete category"
                message={
                    <>
                        Delete <strong>{deleteTarget?.name}</strong>? Its image will be removed
                        and it will disappear from the storefront.
                    </>
                }
                confirmLabel={deleting ? 'Deleting...' : 'Delete'}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </div>
    );
}
