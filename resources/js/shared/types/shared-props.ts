/** Store name, logo and favicon from Company settings (`App\Services\Company\CompanyBranding`). */
export type Branding = {
    name: string;
    /** Null until a logo is uploaded; the UI then shows the name as text. */
    logo_url: string | null;
    favicon_url: string;
    is_custom: boolean;
};

export type SharedProps = {
    name: string;
    branding: Branding;
    cart: {
        count: number;
    };
};
