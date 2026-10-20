import { router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@admin/components/common/ConfirmDialog';
import StatusFilter from '@admin/components/common/StatusFilter';
import TableFooter from '@admin/components/common/TableFooter';
import Button from '@admin/components/ui/button/Button';
import { useListFilters } from '@admin/hooks/useListFilters';
import { destroy, index, update } from '@/routes/admin/sliders';
import { STATUS_ACTIVE, STATUS_INACTIVE } from '@/shared/constants/status';
import SliderFormModal from './SliderFormModal';
import SliderTable from './SliderTable';
import type { Slider, SliderIndexProps } from './types';

type FormTarget = { slider: Slider | null };

export default function SliderManager({ sliders, filters, statuses }: SliderIndexProps) {
    const [formTarget, setFormTarget] = useState<FormTarget | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Slider | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState<number | null>(null);
    const [toggleError, setToggleError] = useState<string | null>(null);

    const { search, setSearch, status, setStatus, perPage, setPerPage, goToPage, processing } =
        useListFilters(index().url, filters);

    const { meta } = sliders;

    const toggleStatus = (slider: Slider) => {
        router.put(
            update(slider.id).url,
            { status: slider.status === STATUS_ACTIVE ? STATUS_INACTIVE : STATUS_ACTIVE },
            {
                preserveScroll: true,
                onStart: () => {
                    setToggleError(null);
                    setTogglingId(slider.id);
                },
                onError: (errors) => setToggleError(errors.status ?? 'Could not change the status.'),
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
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">Sliders</h1>
                <Button size="sm" onClick={() => setFormTarget({ slider: null })}>
                    Add slider
                </Button>
            </div>

            {toggleError && (
                <p
                    role="alert"
                    className="mb-4 rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700 dark:bg-error-500/15 dark:text-error-500"
                >
                    {toggleError}
                </p>
            )}

            <SliderTable
                sliders={sliders.data}
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
                onEdit={(slider) => setFormTarget({ slider })}
                onDelete={setDeleteTarget}
                onToggleStatus={toggleStatus}
            />

            <TableFooter meta={meta} onPageChange={goToPage} />

            {formTarget && (
                <SliderFormModal
                    slider={formTarget.slider}
                    statuses={statuses}
                    defaultSortOrder={meta.total + 1}
                    onClose={() => setFormTarget(null)}
                />
            )}

            <ConfirmDialog
                isOpen={deleteTarget !== null}
                title="Delete slider"
                message={
                    <>
                        Delete <strong>{deleteTarget?.title}</strong>? Its image will be removed and
                        it will disappear from the Home page.
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
