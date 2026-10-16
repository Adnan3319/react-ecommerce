import { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';

interface Service {
  id: number;
  title: string;
  slug: string;
  status: string;
}

export default function ServiceIndex() {
  const [services, setServices] = useState<Service[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    axios
      .get('/api/services')
      .then((response) => {
        // API Resource pagination ba standard structure handle korar jonno
        const resData = response.data.data;
        setServices(Array.isArray(resData) ? resData : resData.data || []);
        setLoading(false);
      })
      .catch((error) => {
        console.error('Error fetching services:', error);
        setLoading(false);
      });
  }, []);

  return (
    <>
      <Head title="Services Management" />

      <div className="w-full">
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">
            Services List
          </h1>
          <Link
            href="/admin/services/create"
            className="rounded-lg bg-brand-500 px-4 py-2 text-white transition hover:bg-brand-600"
          >
            Add New Service
          </Link>
        </div>

        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-800 dark:bg-white/3">
          <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead className="bg-gray-50 dark:bg-white/5">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">ID</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Title</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Slug</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Status</th>
                <th className="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
              {loading ? (
                <tr>
                  <td colSpan={5} className="px-6 py-4 text-center text-sm text-gray-500">
                    Loading...
                  </td>
                </tr>
              ) : services.length > 0 ? (
                services.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-white/90">{item.id}</td>
                    <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-white/90">{item.title}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">{item.slug}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap">
                      <span className="inline-flex rounded-full bg-green-100 px-2 text-xs leading-5 font-semibold text-green-800">
                        {item.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                      <Link href={`/admin/services/${item.id}/edit`} className="mr-3 text-blue-600 hover:text-blue-900">
                        Edit
                      </Link>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={5} className="px-6 py-4 text-center text-sm text-gray-500">
                    No services found. Add your first service!
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </>
  );
}