import type { ListFilters } from '@admin/hooks/useListFilters';
import type { ActiveStatus, Paginated, StatusOption } from '@/shared/types';

/** Mirrors App\Enums\SliderPosition. */
export type SliderPosition = 'hero' | 'promo';

export type Slider = {
    id: number;
    title: string;
    subtitle: string | null;
    image_url: string | null;
    image_srcset: string | null;
    position: SliderPosition;
    position_label: string;
    button_text: string | null;
    button_link: string | null;
    sort_order: number;
    status: ActiveStatus;
    created_at: string | null;
    updated_at: string | null;
};

/** `image_hint` is the upload rule of the position (SliderPosition::imageHint()). */
export type PositionOption = {
    value: SliderPosition;
    label: string;
    image_hint: string;
};

export type SliderListFilters = ListFilters & {
    position: SliderPosition | null;
};

export type SliderIndexProps = {
    sliders: Paginated<Slider>;
    filters: SliderListFilters;
    statuses: StatusOption[];
    positions: PositionOption[];
};
