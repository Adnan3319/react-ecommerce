import React from 'react';

interface DropdownItemProps {
  onItemClick?: () => void;
  tag?: string;
  to?: string;
  onClick?: () => void;
  children: React.ReactNode;
  className?: string;
}

export const DropdownItem: React.FC<DropdownItemProps> = ({ onClick, children, className = '' }) => {
  return (
    <button
      onClick={onClick}
      className={`block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 ${className}`}
    >
      {children}
    </button>
  );
};