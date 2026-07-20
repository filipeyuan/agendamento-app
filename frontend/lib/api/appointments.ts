import { apiRequest } from "@/lib/api/client";
import type { Appointment, AppointmentStatus } from "@/lib/types/appointments";

interface ApiResource<T> {
  data: T;
}

export async function createAppointment(payload: {
  service_id: number;
  start_at: string;
  notes?: string;
}) {
  const { data } = await apiRequest<ApiResource<Appointment>>("/appointments", {
    method: "POST",
    body: JSON.stringify(payload),
  });
  return data;
}

export async function myAppointments() {
  const { data } = await apiRequest<ApiResource<Appointment[]>>("/appointments/mine");
  return data;
}

export async function adminAppointments(filters?: { date?: string; status?: AppointmentStatus }) {
  const params = new URLSearchParams();
  if (filters?.date) params.set("date", filters.date);
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
