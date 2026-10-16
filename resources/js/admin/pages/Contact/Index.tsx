import { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';

interface ContactMessage {
  id: number;
  name: string;
  email: string;
  phone: string;
  subject: string;
  message: string;
  is_read: number | boolean;
  created_at: string;
}

export default function ContactIndex() {
  const [messages, setMessages] = useState<ContactMessage[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    axios
      .get('/api/contact-messages')
      .then((response) => {
        setMessages(response.data.data || response.data);
        setLoading(false);
      })
      .catch((error) => {
        console.error('Error fetching contact messages:', error);
        setLoading(false);
      });
  }, []);

  return (
    <>
      <Head title="Contact Messages" />

      <div className="w-full">
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">
            Contact Messages List
          </h1>
        </div>

        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-800 dark:bg-white/3">
          <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead className="bg-gray-50 dark:bg-white/5">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">ID</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Name</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Email</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Phone</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Subject</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Status</th>
                <th className="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
              {loading ? (
                <tr>
                  <td colSpan={7} className="px-6 py-4 text-center text-sm text-gray-500">
                    Loading...
                  </td>
                </tr>
              ) : messages.length > 0 ? (
                messages.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-white/90">{item.id}</td>
                    <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-white/90">{item.name}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">{item.email}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">{item.phone}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">{item.subject}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap">
                      <span
                        className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${
                          item.is_read
                            ? 'bg-gray-100 text-gray-800'
                            : 'bg-blue-100 text-blue-800'
                        }`}
                      >
                        {item.is_read ? 'Read' : 'Unread'}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                      <Link
                        href={`/admin/contact/${item.id}`}
                        className="text-blue-600 hover:text-blue-900"
                      >
                        View
                      </Link>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={7} className="px-6 py-4 text-center text-sm text-gray-500">
                    No contact messages found.
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