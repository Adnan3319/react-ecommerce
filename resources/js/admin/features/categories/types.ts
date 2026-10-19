import type { ActiveStatus, Paginated, StatusOption } from '@/shared/types';

export type Category = {
    id: number;
    name: string;
    slug: string;
    headline: string | null;
    description: string | null;
    image_url: string | null;
    sort_order: number;
    status: ActiveStatus;
    created_at: string | null;
    updated_at: string | null;
};

export type CategoryFilters = {
    search: string | null;
    status: ActiveStatus | null;
    per_page: number;
};

export type CategoryIndexProps = {
    categories: Paginated<Category>;
    filters: CategoryFilters;
    statuses: StatusOption[];
};
