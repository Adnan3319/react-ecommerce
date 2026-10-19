import type { ButtonHTMLAttributes, FC, ReactNode } from "react";
import { cn } from "@admin/utils";

const VARIANTS = {
  primary:
    "bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600 disabled:bg-brand-300",
  secondary:
    "bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10",
  outline:
    "bg-white text-gray-700 ring-1 ring-gray-300 ring-inset hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/3 dark:hover:text-gray-300",
  danger:
    "bg-error-500 text-white shadow-theme-xs hover:bg-error-600 disabled:bg-error-300",
} as const;

const SIZES = {
  sm: "px-4 py-3 text-sm",
  md: "px-5 py-3.5 text-sm",
} as const;

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: keyof typeof VARIANTS;
  size?: keyof typeof SIZES;
  children: ReactNode;
}

const Button: FC<ButtonProps> = ({
  children,
  className,
  variant = "primary",
  size = "md",
  type = "button",
  ...props
}) => {
  return (
    <button
      type={type}
      className={cn(
        "inline-flex items-center justify-center gap-2 rounded-lg font-medium transition disabled:cursor-not-allowed disabled:opacity-60",
        SIZES[size],
        VARIANTS[variant],
        className,
      )}
      {...props}
    >
      {children}
    </button>
  );
};

export default Button;
