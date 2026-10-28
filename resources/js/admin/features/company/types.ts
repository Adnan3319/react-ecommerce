export interface CompanyData {
    id?: number;
    name: string;
    logo?: string | null;
    email: string;
    phone: string;
    address: string;
    city: string;
    latitude?: string | null;
    longitude?: string | null;
    website: string;
    working_hours?: string | null;
    facebook_url: string;
    instagram_url: string;
    linkedin_url: string;
    about_text: string;
    /** Taka, as typed in the form (the backend stores minor units). */
    delivery_charge_inside: string;
    delivery_charge_outside: string;
}
