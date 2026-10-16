import { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';

interface Career {
  id: number;
  title: string;
  department: string;
  location: string;
  job_type: string;
  deadline: string;
  status: string;
}

export default function CareerIndex() {
  const [careers, setCareers] = useState<Career[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    axios
      .get('/api/careers')
      .then((response) => {
        setCareers(response.data.data || response.data);
        setLoading(false);
      })
      .catch((error) => {
        console.error('Error fetching careers:', error);
        setLoading(false);
      });
  }, []);

  return (
    <>
      <Head title="Career Management" />

      <div className="w-full">
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">
            Job Openings List
          </h1>
          <Link
            href="/admin/career/create"
            className="rounded-lg bg-brand-500 px-4 py-2 text-white transition hover:bg-brand-600"
          >
            Add New Job
          </Link>
        </div>

        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-800 dark:bg-white/3">
          <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead className="bg-gray-50 dark:bg-white/5">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">ID</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Title</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Department</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Location</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Type</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Deadline</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Status</th>
                <th className="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
              {loading ? (
                <tr>
                  <td colSpan={8} className="px-6 py-4 text-center text-sm text-gray-500">
                    Loading...
                  </td>
                </tr>
              ) : careers.length > 0 ? (
                careers.map((job) => (
                  <tr key={job.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-white/90">{job.id}</td>
                    <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-white/90">{job.title}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">{job.department}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">{job.location}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">{job.job_type}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">{job.deadline}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap">
                      <span
                        className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${
                          job.status === 'Active' || job.status === 'Published'
                            ? 'bg-green-100 text-green-800'
                            : 'bg-yellow-100 text-yellow-800'
                        }`}
                      >
                        {job.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                      <Link
                        href={`/admin/career/${job.id}/edit`}
                        className="mr-3 text-blue-600 hover:text-blue-900"
                      >
                        Edit
                      </Link>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={8} className="px-6 py-4 text-center text-sm text-gray-500">
                    No career postings found. Add your first job opening!
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