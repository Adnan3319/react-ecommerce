import { useCallback, useEffect, useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { createPortal } from 'react-dom';
import DataTable from '@admin/components/DataTable';

interface Slider {
  id: number;
  title: string;
  highlight: string | null;
  subtitle: string | null;
  image: string;
  button_text: string | null;
  button_link: string | null;
  sort_order: number;
  status: string;
}

type FormState = {
  title: string;
  subtitle: string;
  image: string;
  button_link: string;
  sort_order: string;
  status: 'Active' | 'Inactive';
};

const EMPTY_FORM: FormState = {
  title: '',
  subtitle: '',
  image: '',
  button_link: '',
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

export default function SliderIndex() {
  const [sliders, setSliders] = useState<Slider[]>([]);
  const [loading, setLoading] = useState(true);

  const [isOpen, setIsOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [formError, setFormError] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  const loadSliders = useCallback(() => {
    return axios
      .get('/api/sliders', { params: { per_page: 50 } })
      .then((response) => {
        const resData = response.data.data;
        setSliders(Array.isArray(resData) ? resData : resData.data || []);
      })
      .catch((error) => {
        console.error('Error fetching sliders:', error);
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    loadSliders();
  }, [loadSliders]);

  function openCreate() {
    setEditingId(null);
    setForm({ ...EMPTY_FORM, sort_order: String(sliders.length + 1) });
    setFormError('');
    setFieldErrors({});
    setIsOpen(true);
  }

  function openEdit(slider: Slider) {
    setEditingId(slider.id);
    setForm({
      title: slider.title,
      subtitle: slider.subtitle ?? '',
      image: slider.image,
      button_link: slider.button_link ?? '',
      sort_order: String(slider.sort_order ?? 0),
      status: slider.status === 'Inactive' ? 'Inactive' : 'Active',
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
      const response = await axios.post('/api/sliders/upload', body);
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

    if (!form.image) {
      setFieldErrors({ image: 'Please upload an image.' });
      return;
    }

    const payload = {
      title: form.title.trim(),
      subtitle: form.subtitle.trim() || null,
      image: form.image,
      button_link: form.button_link.trim() || null,
      sort_order: Number(form.sort_order) || 0,
      status: form.status,
    };

    setSaving(true);
    setFormError('');
    setFieldErrors({});

    try {
      if (editingId === null) {
        await axios.post('/api/sliders', payload);
      } else {
        await axios.put(`/api/sliders/${editingId}`, payload);
      }

      setIsOpen(false);
      await loadSliders();
    } catch (error) {
      const { message, fields } = readError(error);
      setFormError(message);
      setFieldErrors(fields);
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(slider: Slider) {
    if (!window.confirm(`Delete "${slider.title}"?`)) return;

    try {
      await axios.delete(`/api/sliders/${slider.id}`);
      await loadSliders();
    } catch (error) {
      window.alert(readError(error).message);
    }
  }

  async function toggleStatus(slider: Slider) {
    const status = slider.status === 'Active' ? 'Inactive' : 'Active';

    try {
      await axios.put(`/api/sliders/${slider.id}`, { status });
      setSliders((current) =>
        current.map((item) => (item.id === slider.id ? { ...item, status } : item)),
      );
    } catch (error) {
      window.alert(readError(error).message);
    }
  }

  const columns = [
    {
      header: 'Image',
      accessor: 'image',
      render: (row: Slider) => (
        <img
          src={row.image}
          alt={row.title}
          className="h-12 w-20 rounded-md bg-gray-100 object-cover dark:bg-white/5"
        />
      ),
    },
    {
      header: 'Title',
      accessor: 'title',
      render: (row: Slider) => (
        <div>
          <p className="font-medium text-gray-900 dark:text-white/90">
            {row.title}
            {row.highlight && <span className="ml-1 text-red-500">{row.highlight}</span>}
          </p>
          {row.subtitle && (
            <p className="max-w-xs truncate text-xs text-gray-500">{row.subtitle}</p>
          )}
        </div>
      ),
    },
    { header: 'Order', accessor: 'sort_order' },
    {
      header: 'Status',
      accessor: 'status',
      render: (row: Slider) => (
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
      render: (row: Slider) => (
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
      <Head title="Sliders Management" />

      <div className="w-full">
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">Sliders</h1>
          <button
            type="button"
            onClick={openCreate}
            className="rounded-lg bg-brand-500 px-4 py-2 text-white transition hover:bg-brand-600"
          >
            Add New Slider
          </button>
        </div>

        <DataTable
          columns={columns}
          data={sliders}
          loading={loading}
          noDataMessage="No sliders found. Add your first slider!"
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
            className="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900"
          >
            <div className="mb-5 flex items-center justify-between">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                {editingId === null ? 'Add New Slider' : 'Edit Slider'}
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
              {/* Image */}
              <div>
                <label className={LABEL_CLASS}>Image</label>
                <div className="flex items-center gap-4">
                  <div className="flex h-20 w-32 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 text-xs text-gray-400 dark:bg-white/5">
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

              {/* Title */}
              <div>
                <label className={LABEL_CLASS} htmlFor="slider-title">Title</label>
                <input
                  id="slider-title"
                  className={INPUT_CLASS}
                  placeholder="Smart Kitchen"
                  value={form.title}
                  onChange={(event) => setField('title', event.target.value)}
                />
                {fieldErrors.title && <p className={ERROR_CLASS}>{fieldErrors.title}</p>}
              </div>

              <div>
                <label className={LABEL_CLASS} htmlFor="slider-subtitle">Subtitle</label>
                <textarea
                  id="slider-subtitle"
                  rows={2}
                  className={INPUT_CLASS}
                  placeholder="Short description under the title"
                  value={form.subtitle}
                  onChange={(event) => setField('subtitle', event.target.value)}
                />
                {fieldErrors.subtitle && <p className={ERROR_CLASS}>{fieldErrors.subtitle}</p>}
              </div>

              <div>
                <label className={LABEL_CLASS} htmlFor="slider-button-link">Button link</label>
                <input
                  id="slider-button-link"
                  className={INPUT_CLASS}
                  placeholder="/search"
                  value={form.button_link}
                  onChange={(event) => setField('button_link', event.target.value)}
                />
                <p className="mt-1 text-xs text-gray-500">
                  Shows a "Shop Now" button. Leave empty to hide the button.
                </p>
                {fieldErrors.button_link && <p className={ERROR_CLASS}>{fieldErrors.button_link}</p>}
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className={LABEL_CLASS} htmlFor="slider-order">Sort order</label>
                  <input
                    id="slider-order"
                    type="number"
                    className={INPUT_CLASS}
                    value={form.sort_order}
                    onChange={(event) => setField('sort_order', event.target.value)}
                  />
                  {fieldErrors.sort_order && <p className={ERROR_CLASS}>{fieldErrors.sort_order}</p>}
                </div>
                <div>
                  <label className={LABEL_CLASS} htmlFor="slider-status">Status</label>
                  <select
                    id="slider-status"
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
                {saving ? 'Saving...' : editingId === null ? 'Create Slider' : 'Save Changes'}
              </button>
            </div>
          </form>
        </div>,
        document.body,
      )}
    </>
  );
}
