import type { Service } from "@/lib/types/services";

export type AppointmentStatus = "pending" | "confirmed" | "cancelled" | "completed";

export type PaymentStatus = "pending" | "paid" | "refunded";

export type AppointmentSource = "web" | "ai_chat";

export const PAYMENT_STATUS_LABEL: Record<PaymentStatus, string> = {
  pending: "Pagamento pendente",
  paid: "Pago",
  refunded: "Reembolsado",
};

export const PAYMENT_STATUS_BADGE_VARIANT: Record<PaymentStatus, "warning" | "success" | "secondary"> = {
  pending: "warning",
  paid: "success",
  refunded: "secondary",
};

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
  payment_status: PaymentStatus;
  recurring_group_id: string | null;
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
