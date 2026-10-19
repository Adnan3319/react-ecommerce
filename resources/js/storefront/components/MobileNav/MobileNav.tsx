import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/shared/lib';
import type { SharedProps } from '@/shared/types';
import { Icon } from '@/storefront/components/ui';
import { MOBILE_NAV_ITEMS, isActivePath } from '../navigation';

export default function MobileNav() {
    const { url, props } = usePage<SharedProps>();
    const cartCount = props.cart.count;

    return (
        <nav
            aria-label="Mobile"
            className="fixed inset-x-0 bottom-0 z-40 border-t border-neutral-200 bg-white pb-[env(safe-area-inset-bottom)] md:hidden"
        >
            <ul className="flex">
                {MOBILE_NAV_ITEMS.map((item) => {
                    const isActive = isActivePath(url, item.href);
                    const showBadge = item.icon === 'cart' && cartCount > 0;

                    return (
                        <li key={item.label} className="flex-1">
                            <Link
                                href={item.href}
                                aria-current={isActive ? 'page' : undefined}
                                className={cn(
                                    'flex h-14 flex-col items-center justify-center gap-0.5 text-xs font-medium',
                                    isActive
                                        ? 'text-accent-600'
                                        : 'text-neutral-600',
                                )}
                            >
                                <span className="relative">
                                    <Icon name={item.icon} size={22} />
                                    {showBadge && (
                                        <span className="absolute -top-1.5 -right-2.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent-600 px-1 text-[10px] font-semibold text-white">
                                            {cartCount}
                                        </span>
                                    )}
                                </span>
                                {item.label}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
