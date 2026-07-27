import { apiRequest } from "@/lib/api/client";
import type { AppNotification } from "@/lib/types/notifications";

interface NotificationsResponse {
  data: AppNotification[];
  unread_count: number;
  has_more: boolean;
}

export async function listNotifications(limit = 20) {
  return apiRequest<NotificationsResponse>(`/notifications?limit=${limit}`);
}

export async function markNotificationRead(id: string) {
  return apiRequest<void>(`/notifications/${id}/read`, { method: "PATCH" });
}

export async function markAllNotificationsRead() {
  return apiRequest<void>("/notifications/mark-all-read", { method: "POST" });
}
