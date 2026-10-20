import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDialog from '@admin/components/common/ConfirmDialog';
import TableFooter from '@admin/components/common/TableFooter';
import { useListFilters } from '@admin/hooks/useListFilters';
import { STATUS_ACTIVE, STATUS_INACTIVE } from '@/shared/constants/status';
import { create, destroy, index, update } from '@/routes/admin/products';
import type { ActiveStatus } from '@/shared/types';
import ProductFilters from './components/ProductFilters';
import ProductTable from './components/ProductTable';
import type { ProductIndexProps, ProductListItem } from './types';

export default function ProductManager({ products, filters, statuses, categoryTree }: ProductIndexProps) {
    const [deleteTarget, setDeleteTarget] = useState<ProductListItem | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [busyId, setBusyId] = useState<number | null>(null);

    const { search, setSearch, status, setStatus, setExtra, perPage, setPerPage, goToPage, processing } =
        useListFilters(index().url, filters, {
            category_id: filters.category_id ?? undefined,
            featured: filters.featured === null ? undefined : Number(filters.featured),
            stock: filters.stock ?? undefined,
        });

    const patch = (product: ProductListItem, data: { status: ActiveStatus } | { is_featured: boolean }) =>
        router.put(update(product.id).url, data, {
            preserveScroll: true,
            onStart: () => setBusyId(product.id),
            onFinish: () => setBusyId(null),
        });

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        router.delete(destroy(deleteTarget.id).url, {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    };

    return (
        <div className="w-full">
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">Products</h1>
                <Link
                    href={create().url}
                    className="inline-flex items-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600"
                >
                    Add product
                </Link>
            </div>

            <ProductTable
                products={products.data}
                loading={processing}
                toolbar={{
                    search,
                    onSearchChange: setSearch,
                    searchPlaceholder: 'Search by name or SKU',
                    perPage,
                    onPerPageChange: setPerPage,
                    filters: (
                        <ProductFilters
                            filters={filters}
                            status={status}
                            statuses={statuses}
                            categoryTree={categoryTree}
                            onStatusChange={setStatus}
                            onExtraChange={setExtra}
                        />
                    ),
                }}
                busyId={busyId}
                onDelete={setDeleteTarget}
                onToggleStatus={(product) =>
                    patch(product, { status: product.status === STATUS_ACTIVE ? STATUS_INACTIVE : STATUS_ACTIVE })
                }
                onToggleFeatured={(product) => patch(product, { is_featured: !product.is_featured })}
            />

            <TableFooter meta={products.meta} onPageChange={goToPage} />

            <ConfirmDialog
                isOpen={deleteTarget !== null}
                title="Delete product"
                message={
                    <>
                        Delete <strong>{deleteTarget?.name}</strong>? It disappears from the store; its variants and
                        stock history are kept.
                    </>
                }
                confirmLabel={deleting ? 'Deleting...' : 'Delete'}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </div>
    );
}
