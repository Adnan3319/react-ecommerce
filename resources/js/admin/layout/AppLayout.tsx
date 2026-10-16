import type { ReactNode } from 'react';
import { SidebarProvider, useSidebar } from '@admin/context/SidebarContext';
import { cn } from '@admin/utils';
import AppHeader from './AppHeader';
import AppSidebar from './AppSidebar';
import Backdrop from './Backdrop';

const LayoutContent = ({ children }: { children: ReactNode }) => {
    const { isExpanded, isHovered, isMobileOpen } = useSidebar();

    return (
        <div className="min-h-screen xl:flex">
            <AppSidebar />
            <Backdrop />

            <div
                className={cn(
                    'flex-1 transition-[margin] duration-300 ease-in-out',
                    isExpanded || isHovered ? 'xl:ms-72.5' : 'xl:ms-22.5',
                    isMobileOpen ? 'ms-0' : '',
                )}
            >
                <AppHeader />
                <main className="w-full p-4 md:p-6">
                    {children}
                </main>
            </div>
        </div>
    );
};

const AppLayout = ({ children }: { children: ReactNode }) => {
    return (
        <SidebarProvider>
            <LayoutContent>{children}</LayoutContent>
        </SidebarProvider>
    );
};

export default AppLayout;