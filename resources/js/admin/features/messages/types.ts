import type { Paginated } from '@/shared/types';

/** One row of `Admin\ContactMessage\ContactMessageResource`. */
export type ContactMessage = {
    id: number;
    name: string;
    mobile: string;
    email: string | null;
    message: string;
    is_read: boolean;
    created_at: string;
};

export type MessageFilters = {
    search: string | null;
    read: 'unread' | 'read' | null;
    per_page: number;
};

export type MessageIndexProps = {
    messages: Paginated<ContactMessage>;
    filters: MessageFilters;
};

export type MessageShowProps = {
    message: ContactMessage;
    /** Orders placed with the sender's mobile number; `url` is null when there are none. */
    orders: { count: number; url: string | null };
};
