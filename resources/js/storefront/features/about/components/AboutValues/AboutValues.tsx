import { Icon } from '@/storefront/components/ui';
import { ABOUT_VALUES } from '../../static-data';

export default function AboutValues() {
    return (
        <section aria-labelledby="about-values" className="mt-12">
            <h2
                id="about-values"
                className="text-center text-2xl font-bold tracking-tight text-neutral-900 sm:text-3xl"
            >
                Why shop with us
            </h2>
            <ul className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {ABOUT_VALUES.map((value) => (
                    <li
                        key={value.title}
                        className="rounded-card border border-neutral-200 bg-white p-6 shadow-card"
                    >
                        <span className="flex h-12 w-12 items-center justify-center rounded-full bg-accent-50 text-accent-600">
                            <Icon name={value.icon} size={24} />
                        </span>
                        <h3 className="mt-4 text-base font-bold text-neutral-900">
                            {value.title}
                        </h3>
                        <p className="mt-2 text-sm text-neutral-600">
                            {value.description}
                        </p>
                    </li>
                ))}
            </ul>
        </section>
    );
}
