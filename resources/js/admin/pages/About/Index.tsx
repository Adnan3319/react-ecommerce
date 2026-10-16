import { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';

interface AboutUs {
  id: number;
  title: string;
  sub_title: string;
  image: string;
  status: string;
}

export default function AboutIndex() {
  const [aboutData, setAboutData] = useState<AboutUs[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    axios
      .get('/api/about-us')
      .then((response) => {
        setAboutData(response.data.data || response.data);
        setLoading(false);
      })
      .catch((error) => {
        console.error('Error fetching about us data:', error);
        setLoading(false);
      });
  }, []);

  return (
    <>
      <Head title="About Us Management" />

      <div className="w-full">
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">
            About Us List
          </h1>
          <Link
            href="/admin/about/create"
            className="rounded-lg bg-brand-500 px-4 py-2 text-white transition hover:bg-brand-600"
          >
            Add New About Info
          </Link>
        </div>

        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-800 dark:bg-white/3">
          <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead className="bg-gray-50 dark:bg-white/5">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">ID</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Image</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Title</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Sub Title</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Status</th>
                <th className="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
              {loading ? (
                <tr>
                  <td colSpan={6} className="px-6 py-4 text-center text-sm text-gray-500">
                    Loading...
                  </td>
                </tr>
              ) : aboutData.length > 0 ? (
                aboutData.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-white/90">{item.id}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap">
                      {item.image ? (
                        <img src={item.image} alt="" className="h-10 w-10 object-cover rounded" />
                      ) : (
                        <span className="text-gray-400 text-xs">No Image</span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-white/90">{item.title}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">{item.sub_title}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap">
                      <span
                        className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${
                          item.status === 'Active' || item.status === 'Published'
                            ? 'bg-green-100 text-green-800'
                            : 'bg-yellow-100 text-yellow-800'
                        }`}
                      >
                        {item.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                      <Link
                        href={`/admin/about/${item.id}/edit`}
                        className="mr-3 text-blue-600 hover:text-blue-900"
                      >
                        Edit
                      </Link>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={6} className="px-6 py-4 text-center text-sm text-gray-500">
                    No about info found. Add your first record!
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