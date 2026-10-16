import { Icon } from '@/shared/ui';
import { VALUE_PROPS } from '../../static-data';

export default function ValueProps() {
    return (
        <section
            aria-label="Why shop with us"
            className="mx-auto mt-6 max-w-7xl px-4 sm:px-6"
        >
            <ul className="grid grid-cols-2 gap-x-4 gap-y-6 border-y border-neutral-200 py-6 lg:grid-cols-4">
                {VALUE_PROPS.map((item) => (
                    <li
                        key={item.title}
                        className="flex items-center gap-3 lg:justify-center"
                    >
                        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-accent-50 text-accent-600">
                            <Icon name={item.icon} size={22} />
                        </span>
                        <div>
                            <p className="text-sm font-semibold text-neutral-900">
                                {item.title}
                            </p>
                            <p className="text-xs text-neutral-600">
                                {item.description}
                            </p>
                        </div>
                    </li>
                ))}
            </ul>
        </section>
    );
}
