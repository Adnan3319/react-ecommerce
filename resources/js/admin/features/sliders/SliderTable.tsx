import StatusToggle from '@admin/components/common/StatusToggle';
import DataTable from '@admin/components/DataTable';
import type { DataTableToolbar } from '@admin/components/DataTable';
import Badge from '@admin/components/ui/badge/Badge';
import { STATUS_ACTIVE } from '@/shared/constants/status';
import { POSITION_HERO } from './constants';
import type { Slider } from './types';

type SliderTableProps = {
    sliders: Slider[];
    loading: boolean;
    toolbar: DataTableToolbar;
    busyId: number | null;
    onEdit: (slider: Slider) => void;
    onDelete: (slider: Slider) => void;
    onToggleStatus: (slider: Slider) => void;
};

export default function SliderTable({
    sliders,
    loading,
    toolbar,
    busyId,
    onEdit,
    onDelete,
    onToggleStatus,
}: SliderTableProps) {
    const columns = [
        {
            header: 'Image',
            accessor: 'image_url',
            render: (row: Slider) =>
                row.image_url ? (
                    <img
                        src={row.image_url}
                        alt={row.title}
                        width={120}
                        height={50}
                        loading="lazy"
                        className="h-[50px] w-[120px] rounded-md bg-gray-100 object-cover dark:bg-white/5"
                    />
                ) : (
                    <div className="flex h-[50px] w-[120px] items-center justify-center rounded-md bg-gray-100 text-[10px] text-gray-400 dark:bg-white/5">
                        None
                    </div>
                ),
        },
        {
            header: 'Title',
            accessor: 'title',
            render: (row: Slider) => (
                <div>
                    <p className="font-medium text-gray-900 dark:text-white/90">
                        {row.title}
                    </p>
                    {row.subtitle && (
                        <p className="text-xs text-gray-500">{row.subtitle}</p>
                    )}
                </div>
            ),
        },
        {
            header: 'Button',
            accessor: 'button_text',
            render: (row: Slider) =>
                row.button_link ? (
                    <div>
                        <p>{row.button_text || 'Shop Now'}</p>
                        <p className="text-xs text-gray-500">
                            {row.button_link}
                        </p>
                    </div>
                ) : (
                    <span className="text-gray-400">-</span>
                ),
        },
        {
            header: 'Position',
            accessor: 'position',
            render: (row: Slider) => (
                <Badge
                    size="sm"
                    color={row.position === POSITION_HERO ? 'primary' : 'info'}
                >
                    {row.position_label}
                </Badge>
            ),
        },
        { header: 'Order', accessor: 'sort_order' },
        {
            header: 'Status',
            accessor: 'status',
            render: (row: Slider) => (
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
            render: (row: Slider) => (
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
            data={sliders}
            loading={loading}
            toolbar={toolbar}
            noDataMessage="No sliders found. The storefront shows sample slides until you add an active one."
        />
    );
}
