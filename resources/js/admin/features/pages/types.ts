import type { ActiveStatus } from '@/shared/types';

export type Page = {
    id: number;
    title: string;
    sub_title: string | null;
    slug: string;
    content: string | null;
    image_url: string | null;
    status: ActiveStatus;
    created_at?: string;
};

export type PageIndexProps = {
    pages: {
        data: Page[];
        meta: {
            current_page: number;
            last_page: number;
            total: number;
            per_page: number;
            from: number;
            to: number;
            links: any[];
        };
    };
    filters: {
        search?: string;
        status?: string;
    };
    statuses: { value: string; label: string }[];
};