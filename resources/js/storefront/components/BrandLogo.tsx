import { usePage } from '@inertiajs/react';
import type { SharedProps } from '@/shared/types';
import { Image } from '@/storefront/components/ui';

const LOGO_SIZE = 512;

type BrandLogoProps = {
    /** Height class of the uploaded logo, e.g. `h-11`. */
    imageClassName: string;
    /** Text size class used when no logo is uploaded, e.g. `text-xl`. */
    textClassName: string;
    priority?: boolean;
};

/** The uploaded logo, or the store name as text until one is uploaded (Company settings). */
export default function BrandLogo({
    imageClassName,
    textClassName,
    priority = false,
}: BrandLogoProps) {
    const { branding } = usePage<SharedProps>().props;

    if (branding.logo_url === null) {
        return (
            <span
                className={`${textClassName} leading-none font-extrabold tracking-tight text-white`}
            >
                {branding.name}
            </span>
        );
    }

    return (
        <Image
            src={branding.logo_url}
            alt={branding.name}
            width={LOGO_SIZE}
            height={LOGO_SIZE}
            priority={priority}
            className={`${imageClassName} w-auto`}
        />
    );
}
