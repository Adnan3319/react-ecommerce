import {
    ArrowRight,
    Check,
    Clock,
    House,
    Image,
    LifeBuoy,
    Mail,
    MapPin,
    Menu,
    Phone,
    RefreshCw,
    Search,
    ShieldCheck,
    ShoppingCart,
    Star,
    Trash2,
    Truck,
    X,
    Zap,
} from 'lucide-react';
import type { LucideProps } from 'lucide-react';

const ICONS = {
    search: Search,
    cart: ShoppingCart,
    home: House,
    close: X,
    menu: Menu,
    phone: Phone,
    mail: Mail,
    mapPin: MapPin,
    clock: Clock,
    truck: Truck,
    arrowRight: ArrowRight,
    shield: ShieldCheck,
    refresh: RefreshCw,
    support: LifeBuoy,
    image: Image,
    star: Star,
    check: Check,
    trash: Trash2,
    bolt: Zap,
} as const;

export type IconName = keyof typeof ICONS;

export type IconProps = Omit<LucideProps, 'ref'> & {
    name: IconName;
};

/** Thin wrapper over lucide-react; decorative by default, pass `aria-label` for a meaningful icon. */
export default function Icon({ name, size = 24, ...rest }: IconProps) {
    const Component = ICONS[name];

    return (
        <Component
            size={size}
            aria-hidden={rest['aria-label'] ? undefined : true}
            {...rest}
        />
    );
}
