import { formatMoney } from '@/shared/lib';
import { cn } from '@/shared/lib';
import type { DeliveryZone } from '@/storefront/features/product/types';

type DeliveryZonePickerProps = {
    zones: DeliveryZone[];
    value: string;
    error?: string;
    onChange: (zoneKey: string) => void;
};

export default function DeliveryZonePicker({ zones, value, error, onChange }: DeliveryZonePickerProps) {
    return (
        <fieldset className="flex flex-col gap-2">
            <legend className="mb-1 text-sm font-medium text-neutral-800">Delivery area</legend>
            <div className="grid gap-2 sm:grid-cols-2">
                {zones.map((zone) => (
                    <label
                        key={zone.key}
                        className={cn(
                            'flex min-h-12 cursor-pointer items-center gap-3 rounded-control border px-4 py-3 text-sm transition',
                            zone.key === value ? 'border-accent-600 bg-accent-50' : 'border-neutral-300 bg-white hover:border-neutral-400',
                        )}
                    >
                        <input
                            type="radio"
                            name="delivery_zone"
                            value={zone.key}
                            checked={zone.key === value}
                            onChange={() => onChange(zone.key)}
                            className="h-4 w-4 shrink-0 accent-accent-600"
                        />
                        <span className="flex-1 font-medium text-neutral-900">{zone.label}</span>
                        <span className="text-neutral-500">{formatMoney(zone.charge)}</span>
                    </label>
                ))}
            </div>
            {error && (
                <p role="alert" className="text-sm text-red-600">
                    {error}
                </p>
            )}
        </fieldset>
    );
}
