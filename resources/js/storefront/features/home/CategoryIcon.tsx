import type { ReactNode, SVGProps } from 'react';

type CategoryIconProps = Omit<SVGProps<SVGSVGElement>, 'children'> & {
    slug: string;
    size?: number;
};

const ICONS: Record<string, ReactNode> = {
    kitchenware: (
        <>
            <path
                d="M23 15c-3-4 3-6 0-11M32 15c-3-4 3-6 0-11M41 15c-3-4 3-6 0-11"
                stroke="#FEF3C7"
                strokeWidth="2.5"
                strokeLinecap="round"
                fill="none"
            />
            <rect x="1" y="35" width="10" height="6" rx="3" fill="#7C2D12" />
            <rect x="53" y="35" width="10" height="6" rx="3" fill="#7C2D12" />
            <path
                d="M9 31h46v14a14 14 0 0 1-14 14H23A14 14 0 0 1 9 45z"
                fill="#FFFFFF"
            />
            <path d="M12 24c0-5 4-7 20-7s20 2 20 7v7H12z" fill="#FCD34D" />
            <rect x="29" y="12" width="6" height="6" rx="3" fill="#F59E0B" />
            <rect x="9" y="29" width="46" height="4" rx="2" fill="#FDE68A" />
        </>
    ),
    fashion: (
        <>
            <path
                d="M21 7 3 16l6 13 8-4v34h30V25l8 4 6-13-18-9c-2 5-6 8-11 8s-9-3-11-8z"
                fill="#FFFFFF"
            />
            <path
                d="M21 7c2 5 6 8 11 8s9-3 11-8"
                fill="none"
                stroke="#FACC15"
                strokeWidth="3.5"
                strokeLinecap="round"
            />
            <path
                d="M19 34h26M19 42h26"
                stroke="#F9A8D4"
                strokeWidth="3.5"
                strokeLinecap="round"
            />
        </>
    ),
};

const FALLBACK_SLUG = 'kitchenware';

/** White illustrated icon for a category slug, meant for colored surfaces. Decorative. */
export default function CategoryIcon({
    slug,
    size = 48,
    ...rest
}: CategoryIconProps) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width={size}
            height={size}
            viewBox="0 0 64 64"
            aria-hidden="true"
            {...rest}
        >
            {ICONS[slug] ?? ICONS[FALLBACK_SLUG]}
        </svg>
    );
}
