import { router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@admin/components/common/ConfirmDialog';
import StatusFilter from '@admin/components/common/StatusFilter';
import TableFooter from '@admin/components/common/TableFooter';
import Button from '@admin/components/ui/button/Button';
import { useListFilters } from '@admin/hooks/useListFilters';
import { destroy, index, update } from '@/routes/admin/pages';
import PageFormModal from './PageFormModal';
import PageTable from './PageTable';
import { STATUS_ACTIVE, STATUS_INACTIVE } from '@/shared/constants/status';
import type { Page, PageIndexProps } from './types';

type FormTarget = { page: Page | null };

export default function PageManager({
    pages,
    filters,
    statuses,
}: PageIndexProps) {
    const [formTarget, setFormTarget] = useState<FormTarget | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Page | null>(null);
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

    const { meta } = pages;

    const toggleStatus = (page: Page) => {
        router.put(
            update(page.id).url,
            {
                status:
                    page.status === STATUS_ACTIVE
                        ? STATUS_INACTIVE
                        : STATUS_ACTIVE,
            },
            {
                preserveScroll: true,
                onStart: () => setTogglingId(page.id),
                onFinish: () => setTogglingId(null),
            },
        );
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;

        router.delete(destroy(deleteTarget.id).url, {
            preserveScroll: true,
            onStart: () => {
                setDeleteError(null);
                setDeleting(true);
            },
            onError: (errors) =>
                setDeleteError(errors.page ?? 'Could not delete the page.'),
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
                    Pages
                </h1>
                <Button
                    size="sm"
                    onClick={() => setFormTarget({ page: null })}
                >
                    Add New Page
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

            <PageTable
                pages={pages.data}
                loading={processing}
                toolbar={{
                    search,
                    onSearchChange: setSearch,
                    searchPlaceholder: 'Search by title',
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
                onEdit={(page) => setFormTarget({ page })}
                onDelete={setDeleteTarget}
                onToggleStatus={toggleStatus}
            />

            <TableFooter meta={meta} onPageChange={goToPage} />

            {formTarget && (
                <PageFormModal
                    page={formTarget.page}
                    statuses={statuses}
                    onClose={() => setFormTarget(null)}
                />
            )}

            <ConfirmDialog
                isOpen={deleteTarget !== null}
                title="Delete page"
                message={
                    <>
                        Delete <strong>{deleteTarget?.title}</strong>? It will
                        disappear from the storefront.
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