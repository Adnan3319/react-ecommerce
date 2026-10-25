import React from 'react';
import CompanyForm from '@admin/features/company/components/CompanyForm';
import type { CompanyData } from '@admin/features/company/types';

interface EditCompanyProps {
    company: CompanyData;
}

export default function Edit({ company }: EditCompanyProps) {
    return (
        <div className="space-y-6 p-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                    Company Settings
                </h1>
                <p className="text-sm text-gray-500">
                    Manage company profile, contact information, logo, and social handles.
                </p>
            </div>

            <CompanyForm company={company} />
        </div>
    );
}