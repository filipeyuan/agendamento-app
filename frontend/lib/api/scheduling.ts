import { apiRequest } from "@/lib/api/client";
import type { BusinessHour, ScheduleBlock } from "@/lib/types/scheduling";

interface ApiResource<T> {
  data: T;
}

export async function listBusinessHours() {
  const { data } = await apiRequest<ApiResource<BusinessHour[]>>("/admin/business-hours");
  return data;
}

export async function updateBusinessHours(hours: BusinessHour[]) {
  const { data } = await apiRequest<ApiResource<BusinessHour[]>>("/admin/business-hours", {
    method: "PUT",
    body: JSON.stringify({ hours }),
  });
  return data;
}

export async function listScheduleBlocks(range?: { from: string; to: string }) {
  const query = range ? `?from=${range.from}&to=${range.to}` : "";
  const { data } = await apiRequest<ApiResource<ScheduleBlock[]>>(`/admin/schedule-blocks${query}`);
  return data;
}

export async function createScheduleBlock(payload: {
  date: string;
  start_time?: string;
  end_time?: string;
  reason?: string;
}) {
  const { data } = await apiRequest<ApiResource<ScheduleBlock>>("/admin/schedule-blocks", {
    method: "POST",
    body: JSON.stringify(payload),
  });
  return data;
}

export function deleteScheduleBlock(id: number) {
  return apiRequest<void>(`/admin/schedule-blocks/${id}`, { method: "DELETE" });
}
