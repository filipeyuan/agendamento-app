import { apiRequest } from "@/lib/api/client";
import type { Service } from "@/lib/types/services";

interface ApiResource<T> {
  data: T;
}

export async function listServices() {
  const { data } = await apiRequest<ApiResource<Service[]>>("/services");
  return data;
}

export async function listAdminServices() {
  const { data } = await apiRequest<ApiResource<Service[]>>("/admin/services");
  return data;
}

export async function createService(payload: {
  name: string;
  description?: string;
  duration_minutes: number;
  price: number;
}) {
  const { data } = await apiRequest<ApiResource<Service>>("/admin/services", {
    method: "POST",
    body: JSON.stringify(payload),
  });
  return data;
}

export async function updateService(
  id: number,
  payload: Partial<{
    name: string;
    description: string;
    duration_minutes: number;
    price: number;
    active: boolean;
  }>
) {
  const { data } = await apiRequest<ApiResource<Service>>(`/admin/services/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
  return data;
}

export function deleteService(id: number) {
  return apiRequest<void>(`/admin/services/${id}`, { method: "DELETE" });
}

export async function availableSlots(serviceId: number, date: string) {
  const { slots } = await apiRequest<{ slots: string[] }>(
    `/services/${serviceId}/available-slots?date=${date}`
  );
  return slots;
}
