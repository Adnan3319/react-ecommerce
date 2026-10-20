import Select from "@admin/components/form/Select";
import { STATUS_FILTER_ALL } from "@/shared/constants/status";
import type { StatusOption } from "@/shared/types";

interface StatusFilterProps {
  status: string;
  onStatusChange: (status: string) => void;
  statuses: StatusOption[];
}

/** Status dropdown placed next to the table search box (DataTable `toolbar.filters`). */
export default function StatusFilter({
  status,
  onStatusChange,
  statuses,
}: StatusFilterProps) {
  const options = [{ value: STATUS_FILTER_ALL, label: "All statuses" }, ...statuses];

  return (
    <div className="sm:w-44" aria-label="Filter by status">
      <Select
        options={options}
        defaultValue={status}
        onChange={onStatusChange}
        placeholder="All statuses"
      />
    </div>
  );
}
