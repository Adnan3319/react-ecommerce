import React from 'react';
import Input from '@admin/components/form/input/InputField';
import Select from '@admin/components/form/Select';

/** Fixed "rows per page" choices for every admin table (first one is the default). */
export const PER_PAGE_OPTIONS = [10, 20, 50] as const;

const PER_PAGE_SELECT_OPTIONS = PER_PAGE_OPTIONS.map((size) => ({
    value: String(size),
    label: String(size),
}));

interface Column {
    header: string;
    accessor: string;
    render?: (row: any) => React.ReactNode;
}

export interface DataTableToolbar {
    search: string;
    onSearchChange: (search: string) => void;
    searchPlaceholder?: string;
    perPage: number;
    onPerPageChange: (perPage: number) => void;
    /** Extra controls (e.g. a status filter) placed next to the search box. */
    filters?: React.ReactNode;
}

interface DataTableProps {
    columns: Column[];
    data: any[];
    loading: boolean;
    noDataMessage?: string;
    /** Top bar: rows-per-page on the left, filters and search on the right. */
    toolbar?: DataTableToolbar;
}

export default function DataTable({
    columns,
    data,
    loading,
    noDataMessage = 'No data found.',
    toolbar,
}: DataTableProps) {
    return (
        <div className="w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-800 dark:bg-white/3">
            {toolbar && (
                <div className="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                    <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <span>Show</span>
                        <div className="w-24" aria-label="Rows per page">
                            <Select
                                options={PER_PAGE_SELECT_OPTIONS}
                                defaultValue={String(toolbar.perPage)}
                                onChange={(value) =>
                                    toolbar.onPerPageChange(Number(value))
                                }
                                placeholder="Rows"
                            />
                        </div>
                        <span>entries</span>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        {toolbar.filters}
                        <div className="sm:w-64">
                            <Input
                                type="search"
                                value={toolbar.search}
                                onChange={(event) =>
                                    toolbar.onSearchChange(event.target.value)
                                }
                                placeholder={
                                    toolbar.searchPlaceholder ?? 'Search...'
                                }
                                aria-label="Search"
                            />
                        </div>
                    </div>
                </div>
            )}

            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead className="bg-gray-50 dark:bg-white/5">
                        <tr>
                            {columns.map((col, index) => (
                                <th
                                    key={index}
                                    className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    {col.header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
                        {loading ? (
                            <tr>
                                <td
                                    colSpan={columns.length}
                                    className="px-6 py-4 text-center text-sm text-gray-500"
                                >
                                    Loading...
                                </td>
                            </tr>
                        ) : data.length > 0 ? (
                            data.map((row, rowIndex) => (
                                <tr
                                    key={row.id || rowIndex}
                                    className="hover:bg-gray-50 dark:hover:bg-white/5"
                                >
                                    {columns.map((col, colIndex) => (
                                        <td
                                            key={colIndex}
                                            className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-white/90"
                                        >
                                            {col.render
                                                ? col.render(row)
                                                : row[col.accessor]}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td
                                    colSpan={columns.length}
                                    className="px-6 py-4 text-center text-sm text-gray-500"
                                >
                                    {noDataMessage}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
