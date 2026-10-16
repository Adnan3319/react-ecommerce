import React from 'react';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'outline';
  size?: 'sm' | 'md' | 'lg';
  children: React.ReactNode;
  className?: string;
}

const Button: React.FC<ButtonProps> = ({
  children,
  className = '',
  ...props
}) => {
  return (
    <button
      className={`inline-flex items-center justify-center font-medium rounded-lg transition-colors px-4 py-2 bg-brand-500 text-white hover:bg-brand-600 ${className}`}
      {...props}
    >
      {children}
    </button>
  );
};

export default Button;