import type { ListFilters } from '@admin/hooks/useListFilters';
import type { ActiveStatus, Paginated, StatusOption } from '@/shared/types';

export type Slider = {
    id: number;
    title: string;
    subtitle: string | null;
    image_url: string | null;
    image_srcset: string | null;
    button_text: string | null;
    button_link: string | null;
    sort_order: number;
    status: ActiveStatus;
    created_at: string | null;
    updated_at: string | null;
};

export type SliderIndexProps = {
    sliders: Paginated<Slider>;
    filters: ListFilters;
    statuses: StatusOption[];
};
