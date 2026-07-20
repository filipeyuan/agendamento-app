import type { Service } from "@/lib/types/services";

export type AppointmentStatus = "pending" | "confirmed" | "cancelled" | "completed";

export type AppointmentSource = "web" | "ai_chat";

export const APPOINTMENT_STATUS_LABEL: Record<AppointmentStatus, string> = {
  pending: "Pendente",
  confirmed: "Confirmado",
  cancelled: "Cancelado",
  completed: "Concluído",
};

export const APPOINTMENT_STATUS_BADGE_VARIANT: Record<
  AppointmentStatus,
  "warning" | "success" | "destructive" | "secondary"
> = {
  pending: "warning",
  confirmed: "success",
  cancelled: "destructive",
  completed: "secondary",
};

export interface Appointment {
  id: number;
  status: AppointmentStatus;
  source: AppointmentSource;
  start_at: string;
  end_at: string;
  notes: string | null;
  service: Service;
  client?: {
    id: number;
    name: string;
    email: string;
  };
  created_at: string;
}
