export type NotificationType =
  | "payment_confirmed"
  | "appointment_confirmed"
  | "appointment_cancelled"
  | "appointment_rescheduled";

export interface AppNotification {
  id: string;
  type: NotificationType | null;
  message: string | null;
  appointment_id: number | null;
  read_at: string | null;
  created_at: string;
}
