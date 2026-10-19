import { Head } from '@inertiajs/react';
import SliderManager from '@admin/features/sliders/SliderManager';
import type { SliderIndexProps } from '@admin/features/sliders/types';

export default function SliderIndex(props: SliderIndexProps) {
    return (
        <>
            <Head title="Sliders" />
            <SliderManager {...props} />
        </>
    );
}
