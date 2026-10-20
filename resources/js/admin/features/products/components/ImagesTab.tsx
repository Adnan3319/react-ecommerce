import { useState } from 'react';
import type { ChangeEvent } from 'react';
import { MAIN_IMAGE_KEY } from '../constants';
import { newKey } from '../form-state';
import type { FormApi, ImageItem } from '../types';
import FieldError from './FieldError';

type ImagesTabProps = {
    api: FormApi;
    galleryMax: number;
};

const ACCEPT = 'image/jpeg,image/png,image/webp';

function toItem(file: File, key: string): ImageItem {
    return { key, id: null, url: URL.createObjectURL(file), file };
}

function release(item: ImageItem | null): void {
    if (item?.file) {
        URL.revokeObjectURL(item.url);
    }
}

/** Kept images stay before new uploads (the order the server saves), so a move never crosses that line. */
function sameGroup(a: ImageItem, b: ImageItem): boolean {
    return (a.id === null) === (b.id === null);
}

export default function ImagesTab({ api, galleryMax }: ImagesTabProps) {
    const { data, errors, update } = api;
    const [dragIndex, setDragIndex] = useState<number | null>(null);

    const replaceMain = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];

        event.target.value = '';

        if (!file) {
            return;
        }

        const picked = toItem(file, MAIN_IMAGE_KEY);

        update((previous) => {
            release(previous.main);

            return {
                ...previous,
                main: { ...picked, id: previous.main?.id ?? null },
            };
        });
    };

    const addGallery = (event: ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(event.target.files ?? []).slice(
            0,
            Math.max(0, galleryMax - data.gallery.length),
        );
        const picked = files.map((file) => toItem(file, newKey()));

        event.target.value = '';

        update((previous) => ({
            ...previous,
            gallery: [...previous.gallery, ...picked],
        }));
    };

    const removeGallery = (item: ImageItem) =>
        update((previous) => {
            release(item);

            return {
                ...previous,
                gallery: previous.gallery.filter(
                    (candidate) => candidate.key !== item.key,
                ),
                rows: previous.rows.map((row) =>
                    row.imageKey === item.key
                        ? { ...row, imageKey: null }
                        : row,
                ),
            };
        });

    const move = (from: number, to: number) =>
        update((previous) => {
            const source = previous.gallery[from];
            const target = previous.gallery[to];

            if (!source || !target || !sameGroup(source, target)) {
                return previous;
            }

            const gallery = [...previous.gallery];
            gallery.splice(from, 1);
            gallery.splice(to, 0, source);

            return { ...previous, gallery };
        });

    const mainError = errors.main_image;
    const galleryErrors = Object.entries(errors)
        .filter(
            ([key]) =>
                key === 'gallery' ||
                key === 'gallery_keep' ||
                key.startsWith('gallery.'),
        )
        .map(([, message]) => message)
        .filter((message): message is string => Boolean(message));

    return (
        <>
            <section className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                    Main image *
                </h2>
                <p className="mb-4 text-sm text-gray-500">
                    Shown on product cards and first on the product page.
                </p>

                <div className="flex flex-wrap items-start gap-6">
                    <div className="relative h-56 w-56 overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/5">
                        {data.main ? (
                            <img
                                src={data.main.url}
                                alt="Main product"
                                className="h-full w-full object-contain"
                            />
                        ) : (
                            <div className="flex h-full items-center justify-center text-sm text-gray-400">
                                No image yet
                            </div>
                        )}
                        {data.main && (
                            <span className="bg-brand-500 absolute start-2 top-2 rounded-full px-2 py-0.5 text-xs font-medium text-white">
                                Main
                            </span>
                        )}
                    </div>
                    <div className="space-y-3 text-sm text-gray-500">
                        <p>Square image, at least 600×600, max 2MB.</p>
                        <p>
                            JPG, PNG or WebP. Saved as WebP 1200×1200 + 400×400
                            thumbnail.
                        </p>
                        <label className="inline-flex cursor-pointer items-center rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 ring-inset hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700">
                            {data.main ? 'Replace image' : 'Choose image'}
                            <input
                                type="file"
                                accept={ACCEPT}
                                className="sr-only"
                                onChange={replaceMain}
                            />
                        </label>
                        <FieldError message={mainError} />
                    </div>
                </div>
            </section>

            <section className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                <div className="flex items-start justify-between">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                            Gallery
                        </h2>
                        <p className="mb-4 text-sm text-gray-500">
                            Extra photos: other angles, details, colors.
                        </p>
                    </div>
                    <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-400">
                        {data.gallery.length} / {galleryMax} used
                    </span>
                </div>

                <ul className="flex flex-wrap gap-4">
                    {data.gallery.map((item, index) => (
                        <li
                            key={item.key}
                            draggable
                            onDragStart={() => setDragIndex(index)}
                            onDragOver={(event) => event.preventDefault()}
                            onDrop={() => {
                                if (dragIndex !== null) {
                                    move(dragIndex, index);
                                }

                                setDragIndex(null);
                            }}
                            onDragEnd={() => setDragIndex(null)}
                            className="relative h-48 w-48 cursor-grab overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/5"
                        >
                            <img
                                src={item.url}
                                alt={`Gallery ${index + 1}`}
                                className="h-full w-full object-contain"
                            />
                            <button
                                type="button"
                                aria-label={`Remove gallery image ${index + 1}`}
                                onClick={() => removeGallery(item)}
                                className="hover:text-error-500 absolute end-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-white text-gray-600 shadow"
                            >
                                ×
                            </button>
                            <span className="absolute start-2 bottom-2 rounded bg-white/90 px-1.5 py-0.5 text-xs text-gray-500">
                                ⠿ drag
                            </span>
                        </li>
                    ))}
                    {data.gallery.length < galleryMax && (
                        <li>
                            <label className="border-brand-500 bg-brand-50/50 text-brand-500 dark:bg-brand-500/10 flex h-48 w-48 cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed text-sm font-medium">
                                <span className="flex h-9 w-9 items-center justify-center rounded-full bg-white text-xl dark:bg-gray-900">
                                    +
                                </span>
                                Add more
                                <input
                                    type="file"
                                    accept={ACCEPT}
                                    multiple
                                    className="sr-only"
                                    onChange={addGallery}
                                />
                            </label>
                        </li>
                    )}
                </ul>
                {galleryErrors.map((message) => (
                    <FieldError key={message} message={message} />
                ))}
                <p className="mt-3 text-xs text-gray-500">
                    Up to {galleryMax} extra images. "Add more" hides when the
                    limit is reached.
                </p>
            </section>
        </>
    );
}
