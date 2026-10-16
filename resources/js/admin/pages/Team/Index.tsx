import { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';

interface TeamMember {
  id: number;
  name: string;
  designation: string;
  image: string;
  status: string;
}

export default function TeamIndex() {
  const [teamMembers, setTeamMembers] = useState<TeamMember[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    axios
      .get('/api/team-members')
      .then((response) => {
        setTeamMembers(response.data.data || response.data);
        setLoading(false);
      })
      .catch((error) => {
        console.error('Error fetching team members:', error);
        setLoading(false);
      });
  }, []);

  return (
    <>
      <Head title="Team Members Management" />

      <div className="w-full">
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">
            Team Members List
          </h1>
          <Link
            href="/admin/team/create"
            className="rounded-lg bg-brand-500 px-4 py-2 text-white transition hover:bg-brand-600"
          >
            Add New Member
          </Link>
        </div>

        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-800 dark:bg-white/3">
          <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead className="bg-gray-50 dark:bg-white/5">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">ID</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Name</th>
                <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Designation</th>
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
              ) : teamMembers.length > 0 ? (
                teamMembers.map((member) => (
                  <tr key={member.id} className="hover:bg-gray-50 dark:hover:bg-white/5">
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-white/90">{member.id}</td>
                    <td className="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-white/90">{member.name}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-500">{member.designation}</td>
                    <td className="px-6 py-4 text-sm whitespace-nowrap">
                      <span
                        className={`inline-flex rounded-full px-2 text-xs leading-5 font-semibold ${
                          member.status === 'Active'
                            ? 'bg-green-100 text-green-800'
                            : 'bg-red-100 text-red-800'
                        }`}
                      >
                        {member.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                      <Link
                        href={`/admin/team/${member.id}/edit`}
                        className="mr-3 text-blue-600 hover:text-blue-900"
                      >
                        Edit
                      </Link>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={5} className="px-6 py-4 text-center text-sm text-gray-500">
                    No team members found. Add your first member!
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