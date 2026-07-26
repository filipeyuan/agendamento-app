import { apiRequest } from "@/lib/api/client";
import type { AppNotification } from "@/lib/types/notifications";

interface NotificationsResponse {
  data: AppNotification[];
  unread_count: number;
}

export async function listNotifications() {
  return apiRequest<NotificationsResponse>("/notifications");
}

export async function markNotificationRead(id: string) {
  return apiRequest<void>(`/notifications/${id}/read`, { method: "PATCH" });
}

export async function markAllNotificationsRead() {
  return apiRequest<void>("/notifications/mark-all-read", { method: "POST" });
}
