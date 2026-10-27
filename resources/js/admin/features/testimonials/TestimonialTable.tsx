import StatusToggle from '@admin/components/common/StatusToggle';
import DataTable from '@admin/components/DataTable';
import type { DataTableToolbar } from '@admin/components/DataTable';
import Badge from '@admin/components/ui/badge/Badge';
import { STATUS_ACTIVE } from '@/shared/constants/status';
import { EXCERPT_LENGTH } from './constants';
import StarRating from './StarRating';
import type { Testimonial, TestimonialLimits } from './types';

type TestimonialTableProps = {
    testimonials: Testimonial[];
    limits: TestimonialLimits;
    loading: boolean;
    toolbar: DataTableToolbar;
    busyId: number | null;
    onEdit: (testimonial: Testimonial) => void;
    onDelete: (testimonial: Testimonial) => void;
    onToggleStatus: (testimonial: Testimonial) => void;
};

const excerpt = (text: string) =>
    text.length > EXCERPT_LENGTH ? `${text.slice(0, EXCERPT_LENGTH)}…` : text;

export default function TestimonialTable({
    testimonials,
    limits,
    loading,
    toolbar,
    busyId,
    onEdit,
    onDelete,
    onToggleStatus,
}: TestimonialTableProps) {
    const columns = [
        {
            header: 'SL',
            accessor: 'sort_order',
            render: (row: Testimonial, index?: number) => (
                <span className="font-medium text-gray-700 dark:text-gray-300">
                    {row.sort_order ?? (index !== undefined ? index + 1 : '-')}
                </span>
            ),
        },
        {
            header: 'Customer',
            accessor: 'customer_name',
            render: (row: Testimonial) => (
                <div>
                    <p className="font-medium text-gray-900 dark:text-white/90">
                        {row.customer_name}
                    </p>
                    <p className="max-w-xs text-xs whitespace-normal text-gray-500">
                        {excerpt(row.text)}
                    </p>
                </div>
            ),
        },
        {
            header: 'Rating',
            accessor: 'rating',
            render: (row: Testimonial) => (
                <StarRating value={row.rating} max={limits.max_rating} />
            ),
        },
        {
            header: 'Source',
            accessor: 'source',
            render: (row: Testimonial) => (
                <Badge size="sm">{row.source_label}</Badge>
            ),
        },
        {
            header: 'Status',
            accessor: 'status',
            render: (row: Testimonial) => (
                <StatusToggle
                    isActive={row.status === STATUS_ACTIVE}
                    disabled={busyId === row.id}
                    onToggle={() => onToggleStatus(row)}
                />
            ),
        },
        {
            header: 'Actions',
            accessor: 'actions',
            render: (row: Testimonial) => (
                <div className="flex items-center gap-4 text-sm font-medium">
                    <button
                        type="button"
                        onClick={() => onEdit(row)}
                        className="text-brand-500 hover:text-brand-600"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        onClick={() => onDelete(row)}
                        className="text-error-500 hover:text-error-600"
                    >
                        Delete
                    </button>
                </div>
            ),
        },
    ];

    return (
        <DataTable
            columns={columns}
            data={testimonials}
            loading={loading}
            toolbar={toolbar}
            noDataMessage="No testimonials yet. The Home page hides the section until one is active."
        />
    );
}