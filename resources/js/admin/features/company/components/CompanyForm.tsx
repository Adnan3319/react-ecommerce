import React, { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import type { SharedProps } from '@/shared/types';
import Input from '@admin/components/form/input/InputField';
import TextArea from '@admin/components/form/input/TextArea';
import Label from '@admin/components/form/Label';
import Button from '@admin/components/ui/button/Button';
import type { CompanyData } from '../types';

interface CompanyFormProps {
    company: CompanyData;
}

export default function CompanyForm({ company }: CompanyFormProps) {
    const [activeTab, setActiveTab] = useState<
        'general' | 'social' | 'delivery'
    >('general');

    const { data, setData, post, processing, errors } = useForm({
        name: company.name || '',
        email: company.email || '',
        phone: company.phone || '',
        address: company.address || '',
        city: company.city || '',
        working_hours: company.working_hours || '',
        website: company.website || '',
        facebook_url: company.facebook_url || '',
        instagram_url: company.instagram_url || '',
        linkedin_url: company.linkedin_url || '',
        about_text: company.about_text || '',
        logo: null as File | null,
        remove_logo: false,

        // Latitude & Longitude Fields
        latitude: company.latitude || '',
        longitude: company.longitude || '',

        // Delivery charge in taka; the city names the two zones ("Inside <city>" / "Outside <city>")
        delivery_charge_inside: company.delivery_charge_inside,
        delivery_charge_outside: company.delivery_charge_outside,
    });

    const { branding } = usePage<SharedProps>().props;
    const [newLogoPreview, setNewLogoPreview] = useState<string | null>(null);
    const logoPreview = data.remove_logo
        ? null
        : (newLogoPreview ?? branding.logo_url);

    const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData((current) => ({
                ...current,
                logo: file,
                remove_logo: false,
            }));
            setNewLogoPreview(URL.createObjectURL(file));
        }
    };

    const handleLogoRemove = () => {
        setData((current) => ({ ...current, logo: null, remove_logo: true }));
        setNewLogoPreview(null);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post('/admin/company', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setData((current) => ({
                    ...current,
                    logo: null,
                    remove_logo: false,
                }));
                setNewLogoPreview(null);
            },
        });
    };

    return (
        <form onSubmit={handleSubmit} className="max-w-4xl space-y-6">
            {/* Tab Navigation Buttons & Save Button in a Single Row */}
            <div className="flex flex-col gap-4 border-b border-gray-200 pb-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div className="flex flex-wrap">
                    <button
                        type="button"
                        onClick={() => setActiveTab('general')}
                        className={`relative -mb-3 border-b-2 px-4 pb-3 text-sm font-semibold transition-colors ${
                            activeTab === 'general'
                                ? 'text-brand-600 border-brand-600 dark:text-brand-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        }`}
                    >
                        General & Contact Details
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('social')}
                        className={`relative -mb-3 border-b-2 px-4 pb-3 text-sm font-semibold transition-colors ${
                            activeTab === 'social'
                                ? 'text-brand-600 border-brand-600 dark:text-brand-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        }`}
                    >
                        Social Links
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('delivery')}
                        className={`relative -mb-3 border-b-2 px-4 pb-3 text-sm font-semibold transition-colors ${
                            activeTab === 'delivery'
                                ? 'text-brand-600 border-brand-600 dark:text-brand-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        }`}
                    >
                        Delivery Charge
                    </button>
                </div>

                {/* Save Button Moved to Top Right */}
                <div>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Saving...' : 'Save Company Info'}
                    </Button>
                </div>
            </div>

            {/* TAB 1: General & Contact Details */}
            {activeTab === 'general' && (
                <section className="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                            General & Contact Details
                        </h2>
                        <p className="text-sm text-gray-500">
                            Primary information displayed across storefront.
                        </p>
                    </div>

                    {/* Logo Section */}
                    <div className="space-y-2 border-b border-gray-100 pb-6 dark:border-gray-800">
                        <Label>Company Logo</Label>
                        <div className="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                            <div className="relative flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-dashed border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                                {logoPreview ? (
                                    <img
                                        src={logoPreview}
                                        alt="Company Logo"
                                        className="h-full w-full object-contain p-2"
                                    />
                                ) : (
                                    <span className="px-2 text-center text-xs font-medium text-gray-400">
                                        Default: store name
                                    </span>
                                )}
                            </div>

                            <div className="space-y-2">
                                <div className="flex flex-wrap items-center gap-2">
                                    <label
                                        htmlFor="logo"
                                        className="inline-flex cursor-pointer items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                    >
                                        Choose image
                                    </label>
                                    <input
                                        type="file"
                                        id="logo"
                                        accept="image/*"
                                        onChange={handleLogoChange}
                                        className="sr-only"
                                    />
                                    {logoPreview && (
                                        <button
                                            type="button"
                                            onClick={handleLogoRemove}
                                            className="inline-flex items-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 shadow-sm hover:bg-red-50 dark:border-red-900 dark:bg-gray-800"
                                        >
                                            Remove logo
                                        </button>
                                    )}
                                </div>
                                <p className="text-xs text-gray-400">
                                    {data.logo
                                        ? data.logo.name
                                        : 'PNG, JPG or WEBP (Max 2MB). Used in the storefront, admin and browser tab. Without a logo the store name is shown.'}
                                </p>
                                {errors.logo && (
                                    <p className="text-xs text-red-500">
                                        {errors.logo}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label htmlFor="name">Company Name *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                error={Boolean(errors.name)}
                                hint={errors.name}
                            />
                        </div>
                        <div>
                            <Label htmlFor="email">Support Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                                error={Boolean(errors.email)}
                                hint={errors.email}
                            />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label htmlFor="phone">Phone Number</Label>
                            <Input
                                id="phone"
                                value={data.phone}
                                onChange={(e) =>
                                    setData('phone', e.target.value)
                                }
                                error={Boolean(errors.phone)}
                                hint={errors.phone}
                            />
                        </div>
                        <div>
                            <Label htmlFor="website">Website URL</Label>
                            <Input
                                id="website"
                                placeholder="https://example.com"
                                value={data.website}
                                onChange={(e) =>
                                    setData('website', e.target.value)
                                }
                                error={Boolean(errors.website)}
                                hint={errors.website}
                            />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="working_hours">Working Hours</Label>
                        <Input
                            id="working_hours"
                            placeholder="Saturday to Thursday, 10:00 AM to 8:00 PM"
                            value={data.working_hours}
                            onChange={(e) =>
                                setData('working_hours', e.target.value)
                            }
                            error={Boolean(errors.working_hours)}
                            hint={errors.working_hours}
                        />
                    </div>

                    <div>
                        <Label htmlFor="city">City *</Label>
                        <Input
                            id="city"
                            placeholder="Dhaka"
                            value={data.city}
                            onChange={(e) => setData('city', e.target.value)}
                            error={Boolean(errors.city)}
                            hint={
                                errors.city ||
                                'Names the delivery zones: Inside <city> and Outside <city>.'
                            }
                        />
                    </div>

                    <div>
                        <Label htmlFor="address">Office Address</Label>
                        <TextArea
                            id="address"
                            rows={3}
                            value={data.address}
                            onChange={(value) => setData('address', value)}
                            error={Boolean(errors.address)}
                            hint={errors.address}
                        />
                    </div>

                    {/* Latitude and Longitude Fields */}
                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label htmlFor="latitude">Latitude</Label>
                            <Input
                                id="latitude"
                                placeholder="23.8103"
                                value={data.latitude}
                                onChange={(e) =>
                                    setData('latitude', e.target.value)
                                }
                                error={Boolean(errors.latitude)}
                                hint={errors.latitude}
                            />
                        </div>
                        <div>
                            <Label htmlFor="longitude">Longitude</Label>
                            <Input
                                id="longitude"
                                placeholder="90.4125"
                                value={data.longitude}
                                onChange={(e) =>
                                    setData('longitude', e.target.value)
                                }
                                error={Boolean(errors.longitude)}
                                hint={errors.longitude}
                            />
                        </div>
                    </div>

                    {/* Live Map Preview based on Latitude & Longitude */}
                    {data.latitude && data.longitude && (
                        <div className="space-y-2 pt-2">
                            <Label>Map Preview</Label>
                            <div className="h-64 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                                <iframe
                                    title="Location Map Preview"
                                    width="100%"
                                    height="100%"
                                    frameBorder="0"
                                    style={{ border: 0 }}
                                    src={`https://maps.google.com/maps?q=${data.latitude},${data.longitude}&z=15&output=embed`}
                                    allowFullScreen
                                ></iframe>
                            </div>
                        </div>
                    )}

                    <div>
                        <Label htmlFor="about_text">About Description</Label>
                        <TextArea
                            id="about_text"
                            rows={4}
                            value={data.about_text}
                            onChange={(value) => setData('about_text', value)}
                            error={Boolean(errors.about_text)}
                            hint={errors.about_text}
                        />
                    </div>
                </section>
            )}

            {/* TAB 2: Social Links */}
            {activeTab === 'social' && (
                <section className="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                            Social Links
                        </h2>
                        <p className="text-sm text-gray-500">
                            Social media page handles and URLs.
                        </p>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3">
                        <div>
                            <Label htmlFor="facebook_url">Facebook URL</Label>
                            <Input
                                id="facebook_url"
                                placeholder="https://facebook.com/..."
                                value={data.facebook_url}
                                onChange={(e) =>
                                    setData('facebook_url', e.target.value)
                                }
                                error={Boolean(errors.facebook_url)}
                                hint={errors.facebook_url}
                            />
                        </div>
                        <div>
                            <Label htmlFor="instagram_url">Instagram URL</Label>
                            <Input
                                id="instagram_url"
                                placeholder="https://instagram.com/..."
                                value={data.instagram_url}
                                onChange={(e) =>
                                    setData('instagram_url', e.target.value)
                                }
                                error={Boolean(errors.instagram_url)}
                                hint={errors.instagram_url}
                            />
                        </div>
                        <div>
                            <Label htmlFor="linkedin_url">LinkedIn URL</Label>
                            <Input
                                id="linkedin_url"
                                placeholder="https://linkedin.com/in/..."
                                value={data.linkedin_url}
                                onChange={(e) =>
                                    setData('linkedin_url', e.target.value)
                                }
                                error={Boolean(errors.linkedin_url)}
                                hint={errors.linkedin_url}
                            />
                        </div>
                    </div>
                </section>
            )}

            {/* TAB 3: Delivery Charge */}
            {activeTab === 'delivery' && (
                <section className="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">
                            Delivery Charge
                        </h2>
                        <p className="text-sm text-gray-500">
                            Charge in taka for orders delivered inside and
                            outside {data.city || 'your city'}. The storefront
                            order forms use these values.
                        </p>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div>
                            <Label htmlFor="delivery_charge_inside">
                                Inside {data.city || 'city'} (৳)
                            </Label>
                            <Input
                                id="delivery_charge_inside"
                                type="number"
                                min={0}
                                step={0.01}
                                value={data.delivery_charge_inside}
                                onChange={(e) =>
                                    setData(
                                        'delivery_charge_inside',
                                        e.target.value,
                                    )
                                }
                                error={Boolean(errors.delivery_charge_inside)}
                                hint={errors.delivery_charge_inside}
                            />
                        </div>
                        <div>
                            <Label htmlFor="delivery_charge_outside">
                                Outside {data.city || 'city'} (৳)
                            </Label>
                            <Input
                                id="delivery_charge_outside"
                                type="number"
                                min={0}
                                step={0.01}
                                value={data.delivery_charge_outside}
                                onChange={(e) =>
                                    setData(
                                        'delivery_charge_outside',
                                        e.target.value,
                                    )
                                }
                                error={Boolean(errors.delivery_charge_outside)}
                                hint={errors.delivery_charge_outside}
                            />
                        </div>
                    </div>
                </section>
            )}
        </form>
    );
}
