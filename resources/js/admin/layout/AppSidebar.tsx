import { useSidebar } from '@admin/context/SidebarContext';
import { usePage } from '@inertiajs/react';
import { useCallback, useEffect } from 'react';
import { Link, useLocation } from '@admin/lib/router';
import {
    BoxCubeIcon,
    GridIcon,
    HorizontaLDots,
    ListIcon,
    TableIcon,
} from '../icons';
import { cn } from '../utils';
import * as orders from '@/routes/admin/orders';

type NavItem = {
    name: string;
    icon: React.ReactNode;
    path: string;
    badge?: number;
};

const navItems: NavItem[] = [
    {
        name: 'Dashboard',
        icon: <GridIcon fontSize={24} />,
        path: '/admin/dashboard',
    },
    {
        name: 'Orders',
        icon: <ListIcon fontSize={24} />,
        path: orders.index().url,
    },
    {
        name: 'Products',
        icon: <BoxCubeIcon fontSize={24} />,
        path: '/admin/products',
    },
    {
        name: 'Sliders',
        icon: <TableIcon fontSize={24} />,
        path: '/admin/sliders',
    },
    {
        name: 'Categories',
        icon: <ListIcon fontSize={24} />,
        path: '/admin/categories',
    },
];

const AppSidebar: React.FC = () => {
    const {
        isExpanded,
        isMobileOpen,
        isHovered,
        setIsHovered,
        setIsMobileOpen,
    } = useSidebar();
    const location = useLocation();
    const { name: appName, admin } = usePage<{
        name: string;
        admin: { pendingOrders: number } | null;
    }>().props;
    const pendingOrders = admin?.pendingOrders ?? 0;

    useEffect(() => {
        if (isMobileOpen) {
            setIsMobileOpen(false);
        }
    }, [location.pathname]);

    const isActive = useCallback(
        (path: string) =>
            path === '/admin/dashboard'
                ? location.pathname === '/admin/dashboard'
                : location.pathname.startsWith(path),
        [location.pathname],
    );

    const showText = isExpanded || isHovered || isMobileOpen;

    const renderItems = (items: NavItem[]) => (
        <ul className="flex flex-col gap-1">
            {items.map((nav) => (
                <li key={nav.name}>
                    <Link
                        to={nav.path}
                        className={`group menu-item ${
                            isActive(nav.path)
                                ? 'menu-item-active'
                                : 'menu-item-inactive'
                        } ${!showText ? 'xl:justify-center' : 'xl:justify-start'}`}
                    >
                        <span
                            className={`menu-item-icon-size ${
                                isActive(nav.path)
                                    ? 'menu-item-icon-active'
                                    : 'menu-item-icon-inactive'
                            }`}
                        >
                            {nav.icon}
                        </span>
                        {showText && (
                            <span className="menu-item-text">{nav.name}</span>
                        )}
                        {showText && nav.badge ? (
                            <span className="bg-error-500 ms-auto rounded-full px-2 py-0.5 text-xs font-medium text-white">
                                {nav.badge}
                            </span>
                        ) : null}
                    </Link>
                </li>
            ))}
        </ul>
    );

    const groupTitle = (title: string) => (
        <h2
            className={`mb-4 flex text-xs leading-5 text-gray-400 uppercase ${
                !isExpanded && !isHovered
                    ? 'xl:justify-center'
                    : 'justify-start'
            }`}
        >
            {showText ? title : <HorizontaLDots className="size-6" />}
        </h2>
    );

    return (
        <aside
            className={cn(
                'fixed inset-s-0 top-0 z-50 flex h-screen flex-col border-e border-gray-200 bg-white px-5 text-gray-900 transition-all duration-300 ease-in-out xl:translate-x-0 xl:rtl:translate-x-0 dark:border-gray-800 dark:bg-gray-900',
                isExpanded || isMobileOpen
                    ? 'w-72.5'
                    : isHovered
                      ? 'w-72.5'
                      : 'w-22.5',
                isMobileOpen
                    ? 'translate-x-0'
                    : '-translate-x-full rtl:translate-x-full',
            )}
            onMouseEnter={() => !isExpanded && setIsHovered(true)}
            onMouseLeave={() => setIsHovered(false)}
        >
            <div
                className={cn(
                    'flex py-8',
                    !isExpanded && !isHovered
                        ? 'xl:justify-center'
                        : 'justify-start',
                )}
            >
                <Link to="/admin/dashboard" className="flex items-center gap-3">
                    <span className="bg-brand-500 flex size-9 shrink-0 items-center justify-center rounded-lg text-lg font-bold text-white">
                        {appName?.charAt(0)}
                    </span>
                    {showText && (
                        <span className="truncate text-lg font-semibold text-gray-800 dark:text-white/90">
                            {appName}
                        </span>
                    )}
                </Link>
            </div>

            <div className="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear">
                <nav className="mb-6">
                    <div className="flex flex-col gap-4">
                        <div>
                            {groupTitle('Menu')}
                            {renderItems(
                                navItems.map((nav) =>
                                    nav.path === orders.index().url
                                        ? { ...nav, badge: pendingOrders }
                                        : nav,
                                ),
                            )}
                        </div>
                    </div>
                </nav>
            </div>
        </aside>
    );
};

export default AppSidebar;
