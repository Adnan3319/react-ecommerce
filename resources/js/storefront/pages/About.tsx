import React from 'react';
import { Head } from '@inertiajs/react';

interface AboutProps {
    company?: {
        about_heading?: string;
        about_subheading?: string;
        about_bg_image?: string | null;
    };
}

export default function About({ company }: AboutProps) {
    return (
        <div>
            <Head title="About Us" />

            {/* Hero Section with Dynamic Background */}
            <div 
                className="py-24 px-4 bg-cover bg-center text-center relative"
                style={{ 
                    backgroundImage: company?.about_bg_image 
                        ? `url(/storage/${company.about_bg_image})` 
                        : 'none' 
                }}
            >
                {/* Optional overlay for better text readability if background image exists */}
                {company?.about_bg_image && (
                    <div className="absolute inset-0 bg-white/60 dark:bg-black/40"></div>
                )}

                <div className="max-w-3xl mx-auto relative z-10">
                    <span className="text-red-500 font-semibold text-sm uppercase tracking-wider">
                        ABOUT US
                    </span>
                    
                    {/* Dynamic Heading */}
                    <h1 className="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mt-3 mb-4">
                        {company?.about_heading || 'Everyday kitchenware and fashion, made simple'}
                    </h1>

                    {/* Dynamic Subheading */}
                    <p className="text-gray-600 dark:text-gray-300 text-lg">
                        {company?.about_subheading || 'Checkit is an online store for useful kitchen essentials and ladies fashion. We want shopping to be easy: clear prices, honest photos and delivery right to your door.'}
                    </p>
                </div>
            </div>
        </div>
    );
}