import { apiRequest } from "@/lib/api/client";
import type { Appointment, AppointmentStatus } from "@/lib/types/appointments";

interface ApiResource<T> {
  data: T;
}

export async function createAppointment(payload: {
  service_id: number;
  start_at: string;
  notes?: string;
  recurring_occurrences?: number;
}) {
  const { checkout_url } = await apiRequest<{ checkout_url: string }>("/appointments", {
    method: "POST",
    body: JSON.stringify(payload),
  });
  return { checkoutUrl: checkout_url };
}

export async function cancelAppointment(id: number) {
  const { data } = await apiRequest<ApiResource<Appointment>>(`/appointments/${id}/cancel`, {
    method: "PATCH",
  });
  return data;
}

export async function rescheduleAppointment(id: number, startAt: string) {
  const { data } = await apiRequest<ApiResource<Appointment>>(`/appointments/${id}/reschedule`, {
    method: "PATCH",
    body: JSON.stringify({ start_at: startAt }),
  });
  return data;
}

export async function myAppointments() {
  const { data } = await apiRequest<ApiResource<Appointment[]>>("/appointments/mine");
  return data;
}

export async function adminAppointments(filters?: {
  date?: string;
  from?: string;
  to?: string;
  status?: AppointmentStatus;
}) {
  const params = new URLSearchParams();
  if (filters?.date) params.set("date", filters.date);
  if (filters?.from) params.set("from", filters.from);
  if (filters?.to) params.set("to", filters.to);
  if (filters?.status) params.set("status", filters.status);
  const query = params.toString() ? `?${params.toString()}` : "";

  const { data } = await apiRequest<ApiResource<Appointment[]>>(`/admin/appointments${query}`);
  return data;
}

export async function updateAppointmentStatus(id: number, status: AppointmentStatus) {
  const { data } = await apiRequest<ApiResource<Appointment>>(`/admin/appointments/${id}/status`, {
    method: "PATCH",
    body: JSON.stringify({ status }),
  });
  return data;
}
