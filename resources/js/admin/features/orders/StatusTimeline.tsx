import { formatShort } from './format-date';
import type { HistoryEntry } from './types';

function title(entry: HistoryEntry): string {
    switch (entry.kind) {
        case 'placed':
            return 'Order placed';
        case 'note':
            return 'Note';
        case 'change':
            return `${entry.from} → ${entry.to}`;
    }
}

export default function StatusTimeline({ history }: { history: HistoryEntry[] }) {
    return (
        <>
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white/90">Status history</h2>
            <p className="mb-4 text-sm text-gray-500">Every change is logged with who did it.</p>

            <ol className="space-y-4">
                {history.map((entry) => (
                    <li key={entry.id} className="flex gap-3">
                        <span className="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-brand-500" aria-hidden />
                        <div className="text-sm">
                            <p className="font-medium text-gray-900 dark:text-white/90">{title(entry)}</p>
                            <p className="text-xs text-gray-500">
                                {formatShort(entry.at)} · {entry.by ?? 'customer'}
                            </p>
                            {entry.note && <p className="mt-1 text-gray-700 dark:text-gray-300">{entry.note}</p>}
                        </div>
                    </li>
                ))}
            </ol>
        </>
    );
}
