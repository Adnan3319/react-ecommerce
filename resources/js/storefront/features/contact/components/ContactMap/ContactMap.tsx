import { usePage } from '@inertiajs/react';

type CompanyLocation = {
    latitude?: string | null;
    longitude?: string | null;
};

/** Embedded map of the store, shown only when both coordinates are set in Company settings. */
export default function ContactMap() {
    const { company } = usePage<{ company: CompanyLocation | null }>().props;

    if (!company?.latitude || !company.longitude) {
        return null;
    }

    return (
        <div className="h-72 w-full overflow-hidden rounded-card border border-neutral-200 shadow-card">
            <iframe
                title="Store location"
                loading="lazy"
                className="size-full border-0"
                src={`https://maps.google.com/maps?q=${encodeURIComponent(`${company.latitude},${company.longitude}`)}&z=15&output=embed`}
                allowFullScreen
            />
        </div>
    );
}
