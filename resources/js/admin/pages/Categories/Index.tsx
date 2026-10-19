import { useCallback, useEffect, useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { createPortal } from 'react-dom';
import DataTable from '@admin/components/DataTable';

interface Category {
  id: number;
  name: string;
  slug: string;
  headline: string | null;
  description: string | null;
  image: string | null;
  sort_order: number;
  status: string;
}

type FormState = {
  name: string;
  slug: string;
  headline: string;
  description: string;
  image: string;
  sort_order: string;
  status: 'Active' | 'Inactive';
};

const EMPTY_FORM: FormState = {
  name: '',
  slug: '',
  headline: '',
  description: '',
  image: '',
  sort_order: '0',
  status: 'Active',
};

const LABEL_CLASS = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300';
const INPUT_CLASS =
  'w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white/90';
const ERROR_CLASS = 'mt-1 text-xs text-red-600';

/** Pulls a readable message and per-field errors out of an API error. */
function readError(error: unknown): { message: string; fields: Record<string, string> } {
  if (!axios.isAxiosError(error)) {
    return { message: 'Something went wrong. Please try again.', fields: {} };
  }

  const data = error.response?.data as
    | { message?: string; errors?: Record<string, string[] | string> }
    | undefined;

  const fields: Record<string, string> = {};

  Object.entries(data?.errors ?? {}).forEach(([key, value]) => {
    fields[key] = Array.isArray(value) ? value[0] : String(value);
  });

  return {
    message: data?.message ?? 'Something went wrong. Please try again.',
    fields,
  };
}

export default function CategoryIndex() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);

  const [isOpen, setIsOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [formError, setFormError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  const loadCategories = useCallback(() => {
    return axios
      .get('/api/categories', { params: { per_page: 50 } })
      .then((response) => {
        const resData = response.data.data;
        setCategories(Array.isArray(resData) ? resData : resData.data || []);
      })
      .catch((error) => {
        console.error('Error fetching categories:', error);
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    loadCategories();
  }, [loadCategories]);

  function openCreate() {
    setEditingId(null);
    setForm({ ...EMPTY_FORM, sort_order: String(categories.length + 1) });
    setFormError('');
    setFieldErrors({});
    setIsOpen(true);
  }

  function openEdit(category: Category) {
    setEditingId(category.id);
    setForm({
      name: category.name,
      slug: category.slug,
      headline: category.headline ?? '',
      description: category.description ?? '',
      image: category.image ?? '',
      sort_order: String(category.sort_order ?? 0),
      status: category.status === 'Inactive' ? 'Inactive' : 'Active',
    });
    setFormError('');
    setFieldErrors({});
    setIsOpen(true);
  }

  function closeModal() {
    if (saving || uploading) return;
    setIsOpen(false);
  }

  function setField<K extends keyof FormState>(key: K, value: FormState[K]) {
    setForm((current) => ({ ...current, [key]: value }));
  }

  async function handleUpload(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    event.target.value = '';

    if (!file) return;

    const body = new FormData();
    body.append('image', file);

    setUploading(true);
    setFormError('');
    setFieldErrors((current) => ({ ...current, image: '' }));

    try {
      const response = await axios.post('/api/categories/upload', body);
      setField('image', response.data.data.path);
    } catch (error) {
      const { message, fields } = readError(error);
      setFieldErrors((current) => ({ ...current, image: fields.image ?? message }));
    } finally {
      setUploading(false);
    }
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const payload = {
      name: form.name.trim(),
      // Empty slug: created from the name on create, left unchanged on edit.
      slug: form.slug.trim() || undefined,
      headline: form.headline.trim() || null,
      description: form.description.trim() || null,
      image: form.image || null,
      sort_order: Number(form.sort_order) || 0,
      status: form.status,
    };

    setSaving(true);
    setFormError('');
    setFieldErrors({});

    try {
      if (editingId === null) {
        await axios.post('/api/categories', payload);
      } else {
        await axios.put(`/api/categories/${editingId}`, payload);
      }

      setIsOpen(false);
      await loadCategories();
    } catch (error) {
      const { message, fields } = readError(error);
      setFormError(message);
      setFieldErrors(fields);
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(category: Category) {
    if (!window.confirm(`Delete "${category.name}"?`)) return;

    try {
      await axios.delete(`/api/categories/${category.id}`);
      await loadCategories();
    } catch (error) {
      window.alert(readError(error).message);
    }
  }

  async function toggleStatus(category: Category) {
    const status = category.status === 'Active' ? 'Inactive' : 'Active';

    try {
      await axios.put(`/api/categories/${category.id}`, { status });
      setCategories((current) =>
        current.map((item) => (item.id === category.id ? { ...item, status } : item)),
      );
    } catch (error) {
      window.alert(readError(error).message);
    }
  }

  const columns = [
    {
      header: 'Image',
      accessor: 'image',
      render: (row: Category) =>
        row.image ? (
          <img
            src={row.image}
            alt={row.name}
            className="h-12 w-12 rounded-md bg-gray-100 object-cover dark:bg-white/5"
          />
        ) : (
          <div className="flex h-12 w-12 items-center justify-center rounded-md bg-gray-100 text-[10px] text-gray-400 dark:bg-white/5">
            None
          </div>
        ),
    },
    {
      header: 'Name',
      accessor: 'name',
      render: (row: Category) => (
        <span className="font-medium text-gray-900 dark:text-white/90">{row.name}</span>
      ),
    },
    { header: 'Slug', accessor: 'slug' },
    {
      header: 'Description',
      accessor: 'description',
      render: (row: Category) => (
        <p className="max-w-xs truncate text-gray-500" title={row.description ?? ''}>
          {row.description || '-'}
        </p>
      ),
    },
    { header: 'Order', accessor: 'sort_order' },
    {
      header: 'Status',
      accessor: 'status',
      render: (row: Category) => (
        <button
          type="button"
          onClick={() => toggleStatus(row)}
          title="Click to change status"
          className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold transition ${
            row.status === 'Active'
              ? 'bg-green-100 text-green-800 hover:bg-green-200'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          }`}
        >
          {row.status}
        </button>
      ),
    },
    {
      header: 'Actions',
      accessor: 'actions',
      render: (row: Category) => (
        <div className="flex items-center gap-4 text-sm font-medium">
          <button
            type="button"
            onClick={() => openEdit(row)}
            className="text-blue-600 hover:text-blue-900"
          >
            Edit
          </button>
          <button
            type="button"
            onClick={() => handleDelete(row)}
            className="text-red-600 hover:text-red-800"
          >
            Delete
          </button>
        </div>
      ),
    },
  ];

  return (
    <>
      <Head title="Categories Management" />

      <div className="w-full">
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">Categories</h1>
          <button
            type="button"
            onClick={openCreate}
            className="rounded-lg bg-brand-500 px-4 py-2 text-white transition hover:bg-brand-600"
          >
            Add New Category
          </button>
        </div>

        <DataTable
          columns={columns}
          data={categories}
          loading={loading}
          noDataMessage="No categories found. Add your first category!"
        />
      </div>

      {isOpen && createPortal(
        <div
          className="fixed inset-0 z-[100000] flex items-center justify-center bg-black/50 p-4"
          onClick={closeModal}
        >
          <form
            onSubmit={handleSubmit}
            onClick={(event) => event.stopPropagation()}
            noValidate
            className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900"
          >
            <div className="mb-5 flex items-center justify-between">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                {editingId === null ? 'Add New Category' : 'Edit Category'}
              </h2>
              <button
                type="button"
                onClick={closeModal}
                aria-label="Close"
                className="text-2xl leading-none text-gray-400 hover:text-gray-700"
              >
                ×
              </button>
            </div>

            {formError && (
              <p className="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</p>
            )}

            <div className="space-y-4">
              {/* Image (optional) */}
              <div>
                <label className={LABEL_CLASS}>Image (optional)</label>
                <div className="flex items-center gap-4">
                  <div className="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 text-xs text-gray-400 dark:bg-white/5">
                    {form.image ? (
                      <img src={form.image} alt="" className="h-full w-full object-cover" />
                    ) : (
                      'No image'
                    )}
                  </div>
                  <div>
                    <input
                      type="file"
                      accept="image/jpeg,image/png,image/webp"
                      onChange={handleUpload}
                      disabled={uploading}
                      className="block text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200 dark:text-gray-300"
                    />
                    <p className="mt-1 text-xs text-gray-500">
                      {uploading ? 'Uploading...' : 'JPG, PNG or WEBP, up to 4 MB.'}
                    </p>
                  </div>
                </div>
                {fieldErrors.image && <p className={ERROR_CLASS}>{fieldErrors.image}</p>}
              </div>

              <div>
                <label className={LABEL_CLASS} htmlFor="category-name">Name</label>
                <input
                  id="category-name"
                  className={INPUT_CLASS}
                  placeholder="Kitchenware"
                  value={form.name}
                  onChange={(event) => setField('name', event.target.value)}
                />
                {fieldErrors.name && <p className={ERROR_CLASS}>{fieldErrors.name}</p>}
              </div>

              <div>
                <label className={LABEL_CLASS} htmlFor="category-slug">Slug (URL name)</label>
                <input
                  id="category-slug"
                  className={INPUT_CLASS}
                  placeholder="kitchenware"
                  value={form.slug}
                  onChange={(event) => setField('slug', event.target.value)}
                />
                <p className="mt-1 text-xs text-gray-500">
                  Leave empty to create it from the name. Letters, numbers and dashes only.
                </p>
                {fieldErrors.slug && <p className={ERROR_CLASS}>{fieldErrors.slug}</p>}
              </div>

              <div>
                <label className={LABEL_CLASS} htmlFor="category-headline">Headline (optional)</label>
                <input
                  id="category-headline"
                  className={INPUT_CLASS}
                  placeholder="Cook with Joy"
                  value={form.headline}
                  onChange={(event) => setField('headline', event.target.value)}
                />
                {fieldErrors.headline && <p className={ERROR_CLASS}>{fieldErrors.headline}</p>}
              </div>

              <div>
                <label className={LABEL_CLASS} htmlFor="category-description">Description (optional)</label>
                <textarea
                  id="category-description"
                  rows={2}
                  className={INPUT_CLASS}
                  placeholder="Premium kitchen essentials for your home."
                  value={form.description}
                  onChange={(event) => setField('description', event.target.value)}
                />
                {fieldErrors.description && <p className={ERROR_CLASS}>{fieldErrors.description}</p>}
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className={LABEL_CLASS} htmlFor="category-order">Sort order</label>
                  <input
                    id="category-order"
                    type="number"
                    className={INPUT_CLASS}
                    value={form.sort_order}
                    onChange={(event) => setField('sort_order', event.target.value)}
                  />
                  {fieldErrors.sort_order && <p className={ERROR_CLASS}>{fieldErrors.sort_order}</p>}
                </div>
                <div>
                  <label className={LABEL_CLASS} htmlFor="category-status">Status</label>
                  <select
                    id="category-status"
                    className={INPUT_CLASS}
                    value={form.status}
                    onChange={(event) => setField('status', event.target.value as FormState['status'])}
                  >
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                  {fieldErrors.status && <p className={ERROR_CLASS}>{fieldErrors.status}</p>}
                </div>
              </div>
            </div>

            <div className="mt-6 flex justify-end gap-3">
              <button
                type="button"
                onClick={closeModal}
                className="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={saving || uploading}
                className="rounded-lg bg-brand-500 px-5 py-2 text-sm text-white transition hover:bg-brand-600 disabled:opacity-60"
              >
                {saving ? 'Saving...' : editingId === null ? 'Create Category' : 'Save Changes'}
              </button>
            </div>
          </form>
        </div>,
        document.body,
      )}
    </>
  );
}
