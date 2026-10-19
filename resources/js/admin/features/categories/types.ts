import type { Paginated } from '@/shared/types';

export type CategoryStatus = 'active' | 'inactive';

export type Category = {
    id: number;
    name: string;
    slug: string;
    headline: string | null;
    description: string | null;
    image_url: string | null;
    sort_order: number;
    status: CategoryStatus;
    created_at: string | null;
    updated_at: string | null;
};

export type StatusOption = {
    value: CategoryStatus;
    label: string;
};

export type CategoryFilters = {
    search: string | null;
    status: CategoryStatus | null;
    per_page: number;
};

export type CategoryIndexProps = {
    categories: Paginated<Category>;
    filters: CategoryFilters;
    statuses: StatusOption[];
};
