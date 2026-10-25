import React, { useState } from 'react';
import { router, Head } from '@inertiajs/react';
import { Eye, Trash2, Mail, CheckCircle, Clock } from 'lucide-react';

interface ContactMessage {
    id: number;
    name: string;
    email: string | null;
    mobile: string;
    message: string;
    is_read: boolean;
    created_at: string;
}

interface IndexProps {
    messages: {
        data: ContactMessage[];
        links: any[];
    };
}

export default function Index({ messages }: IndexProps) {
    const [selectedMessage, setSelectedMessage] = useState<ContactMessage | null>(null);

    const handleMarkAsRead = (msg: ContactMessage) => {
        if (!msg.is_read) {
            router.patch(route('admin.contact-messages.read', msg.id), {}, {
                preserveScroll: true,
            });
        }
        setSelectedMessage(msg);
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this message?')) {
            router.delete(route('admin.contact-messages.destroy', id), {
                preserveScroll: true,
                onSuccess: () => {
                    if (selectedMessage?.id === id) {
                        setSelectedMessage(null);
                    }
                },
            });
        }
    };

    return (
        <>
            <Head title="Contact Messages" />

            <div className="p-6 max-w-7xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Contact Messages</h1>
                        <p className="text-sm text-gray-500">Manage user inquiries and storefront messages</p>
                    </div>
                </div>

                <div className="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <th className="py-4 px-6">Status</th>
                                    <th className="py-4 px-6">Name</th>
                                    <th className="py-4 px-6">Mobile</th>
                                    <th className="py-4 px-6">Email</th>
                                    <th className="py-4 px-6">Message</th>
                                    <th className="py-4 px-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 text-sm">
                                {messages && messages.data && messages.data.length > 0 ? (
                                    messages.data.map((msg) => (
                                        <tr
                                            key={msg.id}
                                            className={`hover:bg-gray-50/80 transition-colors ${
                                                !msg.is_read ? 'bg-red-50/20 font-medium' : ''
                                            }`}
                                        >
                                            <td className="py-4 px-6">
                                                {msg.is_read ? (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                        <CheckCircle className="size-3.5 text-gray-400" /> Read
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                        <Clock className="size-3.5 text-red-500" /> Unread
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-4 px-6 text-gray-900">{msg.name}</td>
                                            <td className="py-4 px-6 text-gray-600">{msg.mobile}</td>
                                            <td className="py-4 px-6 text-gray-600">{msg.email || 'N/A'}</td>
                                            <td className="py-4 px-6 text-gray-500 max-w-xs truncate">
                                                {msg.message}
                                            </td>
                                            <td className="py-4 px-6 text-right space-x-2">
                                                <button
                                                    onClick={() => handleMarkAsRead(msg)}
                                                    className="p-2 text-gray-600 hover:text-red-600 hover:bg-gray-100 rounded-lg transition-colors"
                                                    title="View Message"
                                                >
                                                    <Eye className="size-4" />
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(msg.id)}
                                                    className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                    title="Delete"
                                                >
                                                    <Trash2 className="size-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={6} className="py-12 text-center text-gray-500">
                                            <Mail className="size-8 mx-auto text-gray-300 mb-2" />
                                            No contact messages found.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* View Message Modal */}
                {selectedMessage && (
                    <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
                        <div className="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl">
                            <div className="flex justify-between items-start border-b pb-3">
                                <div>
                                    <h3 className="text-lg font-bold text-gray-900">{selectedMessage.name}</h3>
                                    <p className="text-xs text-gray-500">{selectedMessage.created_at}</p>
                                </div>
                                <button
                                    onClick={() => setSelectedMessage(null)}
                                    className="text-gray-400 hover:text-gray-600 text-lg font-bold"
                                >
                                    ✕
                                </button>
                            </div>

                            <div className="space-y-2 text-sm text-gray-700">
                                <p><strong>Mobile:</strong> {selectedMessage.mobile}</p>
                                <p><strong>Email:</strong> {selectedMessage.email || 'N/A'}</p>
                                <div className="mt-4 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <p className="text-xs font-semibold text-gray-500 mb-1">Message:</p>
                                    <p className="whitespace-pre-line text-gray-800">{selectedMessage.message}</p>
                                </div>
                            </div>

                            <div className="flex justify-end pt-2">
                                <button
                                    onClick={() => setSelectedMessage(null)}
                                    className="px-4 py-2 bg-gray-900 text-white rounded-xl text-sm font-medium hover:bg-gray-800"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}