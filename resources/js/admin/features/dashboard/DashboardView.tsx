import LowStockList from './LowStockList';
import OrdersChart from './OrdersChart';
import OutcomeRatio from './OutcomeRatio';
import RecentOrdersTable from './RecentOrdersTable';
import TodayMetrics from './TodayMetrics';
import type { DashboardProps } from './types';

export default function DashboardView(props: DashboardProps) {
    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white/90">
                Dashboard
            </h1>

            <TodayMetrics today={props.today} />

            <div className="grid gap-6 xl:grid-cols-3">
                <div className="xl:col-span-2">
                    <OrdersChart trend={props.trend} />
                </div>
                <OutcomeRatio outcome={props.outcome} />
            </div>

            <div className="grid gap-6 xl:grid-cols-2">
                <RecentOrdersTable recent={props.recent} />
                <LowStockList
                    low_stock={props.low_stock}
                    low_stock_threshold={props.low_stock_threshold}
                />
            </div>
        </div>
    );
}
