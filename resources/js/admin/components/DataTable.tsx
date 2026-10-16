import React from 'react';

interface Column {
  header: string;
  accessor: string;
  render?: (row: any) => React.ReactNode;
}

interface DataTableProps {
  columns: Column[];
  data: any[];
  loading: boolean;
  noDataMessage?: string;
}

export default function DataTable({ columns, data, loading, noDataMessage = "No data found." }: DataTableProps) {
  return (
    <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-800 dark:bg-white/3 w-full">
      <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
        <thead className="bg-gray-50 dark:bg-white/5">
          <tr>
            {columns.map((col, index) => (
              <th key={index} className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">
                {col.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
          {loading ? (
            <tr>
              <td colSpan={columns.length} className="px-6 py-4 text-center text-sm text-gray-500">Loading...</td>
            </tr>
          ) : data.length > 0 ? (
            data.map((row, rowIndex) => (
              <tr key={row.id || rowIndex} className="hover:bg-gray-50 dark:hover:bg-white/5">
                {columns.map((col, colIndex) => (
                  <td key={colIndex} className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-white/90">
                    {col.render ? col.render(row) : row[col.accessor]}
                  </td>
                ))}
              </tr>
            ))
          ) : (
            <tr>
              <td colSpan={columns.length} className="px-6 py-4 text-center text-sm text-gray-500">
                {noDataMessage}
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}