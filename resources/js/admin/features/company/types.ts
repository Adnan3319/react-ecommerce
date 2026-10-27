export interface CompanyData {
    id?: number;
    name: string;
    logo?: string | null;
    email: string;
    phone: string;
    address: string;
    website: string;
    facebook_url: string;
    instagram_url: string;
    linkedin_url: string;
    about_text: string;
    about_heading?: string;
    about_subheading?: string;
    about_button_text?: string;
    about_button_link?: string;
    about_bg_image?: string | null;
}