import { router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@admin/components/common/ConfirmDialog';
import StatusFilter from '@admin/components/common/StatusFilter';
import TableFooter from '@admin/components/common/TableFooter';
import Button from '@admin/components/ui/button/Button';
import { useListFilters } from '@admin/hooks/useListFilters';
import { destroy, index, update } from '@/routes/admin/testimonials';
import { STATUS_ACTIVE, STATUS_INACTIVE } from '@/shared/constants/status';
import TestimonialFormModal from './TestimonialFormModal';
import TestimonialTable from './TestimonialTable';
import type { Testimonial, TestimonialIndexProps } from './types';

type FormTarget = { testimonial: Testimonial | null };

export default function TestimonialManager({
    testimonials,
    filters,
    statuses,
    sources,
    limits,
}: TestimonialIndexProps) {
    const [formTarget, setFormTarget] = useState<FormTarget | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Testimonial | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState<number | null>(null);

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

    const { meta } = testimonials;

    const toggleStatus = (testimonial: Testimonial) => {
        router.put(
            update(testimonial.id).url,
            {
                status:
                    testimonial.status === STATUS_ACTIVE
                        ? STATUS_INACTIVE
                        : STATUS_ACTIVE,
            },
            {
                preserveScroll: true,
                onStart: () => setTogglingId(testimonial.id),
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
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">
                    Testimonials
                </h1>
                <Button
                    size="sm"
                    onClick={() => setFormTarget({ testimonial: null })}
                >
                    Add testimonial
                </Button>
            </div>

            <TestimonialTable
                testimonials={testimonials.data}
                limits={limits}
                loading={processing}
                toolbar={{
                    search,
                    onSearchChange: setSearch,
                    searchPlaceholder: 'Search by customer name',
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
                onEdit={(testimonial) => setFormTarget({ testimonial })}
                onDelete={setDeleteTarget}
                onToggleStatus={toggleStatus}
            />

            <TableFooter meta={meta} onPageChange={goToPage} />

            {formTarget && (
                <TestimonialFormModal
                    testimonial={formTarget.testimonial}
                    statuses={statuses}
                    sources={sources}
                    limits={limits}
                    defaultSortOrder={meta.total + 1}
                    onClose={() => setFormTarget(null)}
                />
            )}

            <ConfirmDialog
                isOpen={deleteTarget !== null}
                title="Delete testimonial"
                message={
                    <>
                        Delete the testimonial of{' '}
                        <strong>{deleteTarget?.customer_name}</strong>? It
                        disappears from the Home page.
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
