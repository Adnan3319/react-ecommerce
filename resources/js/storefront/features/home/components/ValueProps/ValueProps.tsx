import { Icon } from '@/storefront/components/ui';
import { VALUE_PROPS } from '../../static-data';

/** Glass feature strip; sits on top of the hero image. */
export default function ValueProps() {
    return (
        <ul
            aria-label="Why shop with us"
            className="mx-auto grid max-w-7xl grid-cols-2 gap-x-4 gap-y-5 border-t border-white/10 px-4 pt-6 pb-14 sm:px-6 lg:grid-cols-4"
        >
            {VALUE_PROPS.map((item) => (
                <li
                    key={item.title}
                    className="flex items-center gap-3 lg:justify-center"
                >
                    <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/20 backdrop-blur">
                        <Icon name={item.icon} size={20} />
                    </span>
                    <div>
                        <p className="text-sm font-semibold">{item.title}</p>
                        <p className="text-xs text-white/60">
                            {item.description}
                        </p>
                    </div>
                </li>
            ))}
        </ul>
    );
}
