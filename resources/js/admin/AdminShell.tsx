import '../../css/admin.css';
import 'flatpickr/dist/flatpickr.css';
import 'jsvectormap/dist/jsvectormap.css';
import 'simplebar-react/dist/simplebar.min.css';
import 'swiper/swiper-bundle.css';
import './i18n';

import type { ReactNode } from 'react';
import { AppWrapper } from '@admin/components/common/PageMeta';
import { ScrollToTop } from '@admin/components/common/ScrollToTop';
import { LanguageProvider } from '@admin/context/LanguageContext';
import { ThemeProvider } from '@admin/context/ThemeContext';
import AppLayout from '@admin/layout/AppLayout';

export default function AdminShell({
    children,
    withLayout = true,
}: {
    children: ReactNode;
    withLayout?: boolean;
}) {
    return (
        <ThemeProvider>
            <LanguageProvider>
                <AppWrapper>
                    <ScrollToTop />
                    {withLayout ? <AppLayout>{children}</AppLayout> : children}
                </AppWrapper>
            </LanguageProvider>
        </ThemeProvider>
    );
}