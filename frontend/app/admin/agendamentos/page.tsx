"use client";

import { useState } from "react";
import useSWR from "swr";
import { Check, CheckCheck, ClipboardList, X } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select } from "@/components/ui/select";
import { Spinner } from "@/components/ui/spinner";
import { adminAppointments, updateAppointmentStatus } from "@/lib/api/appointments";
import {
  APPOINTMENT_STATUS_BADGE_VARIANT,
  APPOINTMENT_STATUS_LABEL,
  type Appointment,
  type AppointmentStatus,
} from "@/lib/types/appointments";

function formatDateTime(iso: string) {
  return new Date(iso).toLocaleString("pt-BR", { dateStyle: "short", timeStyle: "short" });
}

function AgendamentosAdminPanel() {
  const [date, setDate] = useState("");
  const [status, setStatus] = useState<AppointmentStatus | "">("");

  const {
    data: appointments,
    isLoading,
    mutate: reloadAppointments,
  } = useSWR(["admin-appointments", date, status], () =>
    adminAppointments({
      date: date || undefined,
      status: status || undefined,
    })
  );

  async function handleStatusChange(appointment: Appointment, newStatus: AppointmentStatus) {
    await updateAppointmentStatus(appointment.id, newStatus);
    reloadAppointments();
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="flex flex-wrap items-end gap-4">
        <div>
          <Label htmlFor="filter-date">Data</Label>
          <Input id="filter-date" type="date" value={date} onChange={(e) => setDate(e.target.value)} />
        </div>
        <div>
          <Label htmlFor="filter-status">Status</Label>
          <Select
            id="filter-status"
            value={status}
            onChange={(e) => setStatus(e.target.value as AppointmentStatus | "")}
          >
            <option value="">Todos</option>
            {Object.entries(APPOINTMENT_STATUS_LABEL).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </Select>
        </div>
      </div>

      {isLoading && (
        <div className="flex justify-center py-16">
          <Spinner className="h-6 w-6 text-muted-foreground" />
        </div>
      )}

      {appointments?.length === 0 && (
        <EmptyState
          icon={ClipboardList}
          title="Nenhum agendamento encontrado"
          description="Ajuste os filtros ou aguarde novos agendamentos chegarem."
        />
      )}

      <div className="flex flex-col gap-3">
        {appointments?.map((appointment) => (
          <Card key={appointment.id}>
            <CardContent className="flex flex-wrap items-center justify-between gap-4 py-4">
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-medium text-foreground">{appointment.service.name}</span>
                  <Badge variant={APPOINTMENT_STATUS_BADGE_VARIANT[appointment.status]}>
                    {APPOINTMENT_STATUS_LABEL[appointment.status]}
                  </Badge>
                </div>
                <p className="text-sm text-muted-foreground">
                  {formatDateTime(appointment.start_at)} · {appointment.client?.name} (
                  {appointment.client?.email})
                </p>
                {appointment.notes && (
                  <p className="text-sm text-muted-foreground">Obs: {appointment.notes}</p>
                )}
              </div>

              {appointment.status === "pending" && (
                <div className="flex gap-2">
                  <Button size="sm" onClick={() => handleStatusChange(appointment, "confirmed")}>
                    <Check className="h-4 w-4" />
                    Confirmar
                  </Button>
                  <Button
                    variant="destructive"
                    size="sm"
                    onClick={() => handleStatusChange(appointment, "cancelled")}
                  >
                    <X className="h-4 w-4" />
                    Cancelar
                  </Button>
                </div>
              )}

              {appointment.status === "confirmed" && (
                <div className="flex gap-2">
                  <Button size="sm" onClick={() => handleStatusChange(appointment, "completed")}>
                    <CheckCheck className="h-4 w-4" />
                    Concluir
                  </Button>
                  <Button
                    variant="destructive"
                    size="sm"
                    onClick={() => handleStatusChange(appointment, "cancelled")}
                  >
                    <X className="h-4 w-4" />
                    Cancelar
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}

export default function AgendamentosAdminPage() {
  return (
    <RequireAuth role="admin">
      <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-10">
        <h1 className="mb-6 text-2xl font-semibold text-foreground">Agendamentos</h1>
        <AgendamentosAdminPanel />
      </main>
    </RequireAuth>
  );
}
