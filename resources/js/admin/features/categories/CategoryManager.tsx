import { router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@admin/components/common/ConfirmDialog';
import StatusFilter from '@admin/components/common/StatusFilter';
import TableFooter from '@admin/components/common/TableFooter';
import Button from '@admin/components/ui/button/Button';
import { useListFilters } from '@admin/hooks/useListFilters';
import { destroy, index, update } from '@/routes/admin/categories';
import CategoryFormModal from './CategoryFormModal';
import CategoryTable from './CategoryTable';
import { STATUS_ACTIVE, STATUS_INACTIVE } from '@/shared/constants/status';
import type { Category, CategoryIndexProps } from './types';

type FormTarget = { category: Category | null };

export default function CategoryManager({
    categories,
    filters,
    statuses,
    parents,
}: CategoryIndexProps) {
    const [formTarget, setFormTarget] = useState<FormTarget | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Category | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState<number | null>(null);
    const [deleteError, setDeleteError] = useState<string | null>(null);

    const {
        search,
        setSearch,
        status,
        setStatus,
        perPage,
        setPerPage,
        goToPage,
        processing,
    } = useListFilters(index().url, filters);

    const { meta } = categories;

    const toggleStatus = (category: Category) => {
        router.put(
            update(category.id).url,
            {
                status:
                    category.status === STATUS_ACTIVE
                        ? STATUS_INACTIVE
                        : STATUS_ACTIVE,
            },
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
            onStart: () => {
                setDeleteError(null);
                setDeleting(true);
            },
            onError: (errors) =>
                setDeleteError(
                    errors.category ?? 'Could not delete the category.',
                ),
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    };

    return (
        <div className="w-full">
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">
                    Categories
                </h1>
                <Button
                    size="sm"
                    onClick={() => setFormTarget({ category: null })}
                >
                    Add category
                </Button>
            </div>

            {deleteError && (
                <p
                    role="alert"
                    className="bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-500 mb-4 rounded-lg px-4 py-3 text-sm"
                >
                    {deleteError}
                </p>
            )}

            <CategoryTable
                categories={categories.data}
                loading={processing}
                toolbar={{
                    search,
                    onSearchChange: setSearch,
                    searchPlaceholder: 'Search by name',
                    perPage,
                    onPerPageChange: setPerPage,
                    filters: (
                        <StatusFilter
                            status={status}
                            onStatusChange={setStatus}
                            statuses={statuses}
                        />
                    ),
                }}
                busyId={togglingId}
                onEdit={(category) => setFormTarget({ category })}
                onDelete={setDeleteTarget}
                onToggleStatus={toggleStatus}
            />

            <TableFooter meta={meta} onPageChange={goToPage} />

            {formTarget && (
                <CategoryFormModal
                    category={formTarget.category}
                    statuses={statuses}
                    parents={parents}
                    defaultSortOrder={meta.total + 1}
                    onClose={() => setFormTarget(null)}
                />
            )}

            <ConfirmDialog
                isOpen={deleteTarget !== null}
                title="Delete category"
                message={
                    <>
                        Delete <strong>{deleteTarget?.name}</strong>? Its image
                        will be removed and it will disappear from the
                        storefront.
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
