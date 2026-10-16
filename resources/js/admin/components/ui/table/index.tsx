import React from 'react';

export const Table = ({ children, className = '' }: { children: React.ReactNode; className?: string }) => (
  <div className={`overflow-x-auto ${className}`}><table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">{children}</table></div>
);

export const TableHeader = ({ children }: { children: React.ReactNode }) => <thead className="bg-gray-50 dark:bg-gray-800">{children}</thead>;
export const TableBody = ({ children }: { children: React.ReactNode }) => <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">{children}</tbody>;
export const TableRow = ({ children }: { children: React.ReactNode }) => <tr>{children}</tr>;
export const TableCell = ({ children }: { children: React.ReactNode }) => <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{children}</td>;