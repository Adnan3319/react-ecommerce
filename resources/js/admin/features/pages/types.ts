import type { ListFilters } from '@admin/hooks/useListFilters';
import type { ActiveStatus, Paginated, StatusOption } from '@/shared/types';

export type Page = {
    id: number;
    title: string;
    sub_title: string | null;
    slug: string;
    content: string | null;
    image_url: string | null;
    status: ActiveStatus;
    created_at: string | null;
};

export type PageIndexProps = {
    pages: Paginated<Page>;
    filters: ListFilters;
    statuses: StatusOption[];
};
