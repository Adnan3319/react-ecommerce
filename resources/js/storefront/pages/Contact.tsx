import React from 'react';
import { useForm } from '@inertiajs/react';

interface ContactProps {
    company?: {
        phone?: string;
        email?: string;
        address?: string;
        working_hours?: string;
        latitude?: string;
        longitude?: string;
    };
}

export default function Contact({ company }: ContactProps) {
    const { data, setData, post, processing, errors, reset, recentlySuccessful } = useForm({
        name: '',
        email: '',
        mobile: '',
        message: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/contact', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <div className="max-w-7xl mx-auto px-4 py-10">
            <div className="mb-8">
                <p className="text-sm text-gray-500 mb-2">Home / Contact</p>
                <h1 className="text-3xl font-bold text-gray-900">Contact Us</h1>
                <p className="text-gray-600 mt-2">
                    Have a question about a product or your order? Send us a message or reach us using the details below.
                </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                {/* Left Side: Contact Information Cards & Map */}
                <div className="space-y-4">
                    <div className="p-5 border border-gray-100 rounded-2xl bg-white shadow-sm flex items-center gap-4">
                        <div className="p-3 bg-red-50 text-red-500 rounded-xl">
                            📞
                        </div>
                        <div>
                            <p className="text-xs text-gray-400 font-medium">Phone</p>
                            <p className="text-sm font-semibold text-gray-800">
                                {company?.phone || '01404416192'}
                            </p>
                        </div>
                    </div>

                    <div className="p-5 border border-gray-100 rounded-2xl bg-white shadow-sm flex items-center gap-4">
                        <div className="p-3 bg-red-50 text-red-500 rounded-xl">
                            ✉️
                        </div>
                        <div>
                            <p className="text-xs text-gray-400 font-medium">Email</p>
                            <p className="text-sm font-semibold text-gray-800">
                                {company?.email || 'arafatulalam321@gmail.com'}
                            </p>
                        </div>
                    </div>

                    <div className="p-5 border border-gray-100 rounded-2xl bg-white shadow-sm flex items-center gap-4">
                        <div className="p-3 bg-red-50 text-red-500 rounded-xl">
                            📍
                        </div>
                        <div>
                            <p className="text-xs text-gray-400 font-medium">Address</p>
                            <p className="text-sm font-semibold text-gray-800">
                                {company?.address || 'Didar Market'}
                            </p>
                        </div>
                    </div>

                    <div className="p-5 border border-gray-100 rounded-2xl bg-white shadow-sm flex items-center gap-4">
                        <div className="p-3 bg-red-50 text-red-500 rounded-xl">
                            ⏰
                        </div>
                        <div>
                            <p className="text-xs text-gray-400 font-medium">Working hours</p>
                            <p className="text-sm font-semibold text-gray-800">
                                {company?.working_hours || 'Sun to Thus (10:00am - 6:00pm)'}
                            </p>
                        </div>
                    </div>

                    {/* Google Map Live Integration */}
                    {company?.latitude && company?.longitude && (
                        <div className="rounded-2xl overflow-hidden border border-gray-100 shadow-sm h-72 w-full mt-4">
                            <iframe
                                title="Company Location Map"
                                width="100%"
                                height="100%"
                                frameBorder="0"
                                style={{ border: 0 }}
                                src={`https://maps.google.com/maps?q=${company.latitude},${company.longitude}&z=15&output=embed`}
                                allowFullScreen
                            ></iframe>
                        </div>
                    )}
                </div>

                {/* Right Side: Contact Form */}
                <div className="p-6 bg-white border border-gray-100 rounded-2xl shadow-sm">
                    <h2 className="text-xl font-bold text-gray-900 mb-6">Send us a message</h2>

                    {recentlySuccessful && (
                        <div className="mb-4 p-3 bg-emerald-50 text-emerald-700 text-sm rounded-xl border border-emerald-200">
                            Your message has been sent successfully!
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Your name</label>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full rounded-xl border border-gray-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-gray-50/30"
                                placeholder="Enter your name"
                                required
                            />
                            {errors.name && <p className="mt-1 text-xs text-red-500">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="w-full rounded-xl border border-gray-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-gray-50/30"
                                placeholder="example@gmail.com"
                            />
                            {errors.email && <p className="mt-1 text-xs text-red-500">{errors.email}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Mobile number</label>
                            <input
                                type="text"
                                value={data.mobile}
                                onChange={(e) => setData('mobile', e.target.value)}
                                className="w-full rounded-xl border border-gray-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-gray-50/30"
                                placeholder="01XXXXXXXXX"
                                required
                            />
                            {errors.mobile && <p className="mt-1 text-xs text-red-500">{errors.mobile}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Message</label>
                            <textarea
                                value={data.message}
                                onChange={(e) => setData('message', e.target.value)}
                                className="w-full rounded-xl border border-gray-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-gray-50/30"
                                rows={4}
                                placeholder="Write your message here..."
                                required
                            />
                            {errors.message && <p className="mt-1 text-xs text-red-500">{errors.message}</p>}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-xl bg-red-500 py-3.5 text-sm font-semibold text-white shadow-sm hover:bg-red-600 transition duration-200 disabled:opacity-50"
                        >
                            {processing ? 'Sending...' : 'Send Message'}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}