import type { FC, ReactNode } from 'react';

interface TableProps {
    children: ReactNode;
    className?: string;
}

interface TableCellProps {
    children: ReactNode;
    isHeader?: boolean;
    className?: string;
}

export const Table: FC<TableProps> = ({ children, className }) => (
    <table className={`min-w-full ${className ?? ''}`}>{children}</table>
);

export const TableHeader: FC<TableProps> = ({ children, className }) => (
    <thead className={className}>{children}</thead>
);

export const TableBody: FC<TableProps> = ({ children, className }) => (
    <tbody className={className}>{children}</tbody>
);

export const TableRow: FC<TableProps> = ({ children, className }) => (
    <tr className={className}>{children}</tr>
);

export const TableCell: FC<TableCellProps> = ({
    children,
    isHeader = false,
    className,
}) => {
    const CellTag = isHeader ? 'th' : 'td';

    return <CellTag className={className}>{children}</CellTag>;
};
