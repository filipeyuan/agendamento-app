import { apiRequest } from "@/lib/api/client";

export async function getGoogleCalendarConnectUrl() {
  const { url } = await apiRequest<{ url: string }>("/admin/google-calendar/connect");
  return url;
}

export async function getGoogleCalendarStatus() {
  const { connected } = await apiRequest<{ connected: boolean }>("/admin/google-calendar/status");
  return connected;
}

export function disconnectGoogleCalendar() {
  return apiRequest<void>("/admin/google-calendar/disconnect", { method: "DELETE" });
}
