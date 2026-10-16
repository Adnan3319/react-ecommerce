import { TESTIMONIALS } from '../../static-data';

export default function Testimonials() {
    return (
        <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
            <h2 className="mb-6 text-center text-xl font-bold tracking-tight text-neutral-900 sm:text-2xl">
                What Our Customers Say
            </h2>
            <ul className="grid gap-4 md:grid-cols-3">
                {TESTIMONIALS.map((item) => (
                    <li key={item.id}>
                        <figure className="flex h-full gap-3 rounded-card border border-neutral-200 bg-accent-50/40 p-5">
                            <span
                                aria-hidden="true"
                                className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent-100 text-xl font-bold text-accent-700"
                            >
                                &ldquo;
                            </span>
                            <div className="flex flex-col gap-3">
                                <blockquote className="text-sm text-neutral-700">
                                    {item.quote}
                                </blockquote>
                                <figcaption className="text-sm font-semibold text-neutral-900">
                                    {item.author}
                                </figcaption>
                            </div>
                        </figure>
                    </li>
                ))}
            </ul>
        </section>
    );
}
