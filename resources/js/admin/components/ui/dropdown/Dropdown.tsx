import React from 'react';

interface DropdownProps {
    isOpen: boolean;
    onClose: () => void;
    children: React.ReactNode;
    className?: string;
}

export const Dropdown: React.FC<DropdownProps> = ({
    isOpen,
    children,
    className = '',
}) => {
    if (!isOpen) return null;
    return (
        <div
            className={`ring-opacity-5 absolute right-0 mt-2 w-48 rounded-lg bg-white shadow-lg ring-1 ring-black dark:bg-gray-800 ${className}`}
        >
            {children}
        </div>
    );
};
