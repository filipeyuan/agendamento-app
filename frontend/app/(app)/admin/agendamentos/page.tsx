"use client";

import { useMemo, useState } from "react";
import type { DatesSetArg, EventClickArg, EventInput } from "@fullcalendar/core";
import ptBrLocale from "@fullcalendar/core/locales/pt-br";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";
import listPlugin from "@fullcalendar/list";
import FullCalendar from "@fullcalendar/react";
import timeGridPlugin from "@fullcalendar/timegrid";
import useSWR from "swr";
import { Check, CheckCheck, MousePointerClick, Repeat, X } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Label } from "@/components/ui/label";
import { Select } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { adminAppointments, updateAppointmentStatus } from "@/lib/api/appointments";
import { listTeam } from "@/lib/api/team";
import { toLocalIsoDate } from "@/lib/utils/date";
import {
  APPOINTMENT_STATUS_BADGE_VARIANT,
  APPOINTMENT_STATUS_LABEL,
  PAYMENT_STATUS_BADGE_VARIANT,
  PAYMENT_STATUS_LABEL,
  type Appointment,
  type AppointmentStatus,
} from "@/lib/types/appointments";

const STATUS_COLOR: Record<AppointmentStatus, { background: string; text: string }> = {
  pending: { background: "var(--warning)", text: "var(--warning-foreground)" },
  confirmed: { background: "var(--success)", text: "var(--success-foreground)" },
  cancelled: { background: "var(--destructive)", text: "var(--destructive-foreground)" },
  completed: { background: "var(--secondary)", text: "var(--secondary-foreground)" },
};

function formatDateTime(iso: string) {
  return new Date(iso).toLocaleString("pt-BR", { dateStyle: "short", timeStyle: "short" });
}

function AppointmentDetail({
  appointment,
  onStatusChange,
}: {
  appointment: Appointment;
  onStatusChange: (status: AppointmentStatus) => void;
}) {
  return (
    <Card>
      <CardContent className="flex flex-wrap items-center justify-between gap-4 py-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="font-medium text-foreground">{appointment.service.name}</span>
            <Badge variant={APPOINTMENT_STATUS_BADGE_VARIANT[appointment.status]}>
              {APPOINTMENT_STATUS_LABEL[appointment.status]}
            </Badge>
            <Badge variant={PAYMENT_STATUS_BADGE_VARIANT[appointment.payment_status]}>
              {PAYMENT_STATUS_LABEL[appointment.payment_status]}
            </Badge>
            {appointment.recurring_group_id && (
              <Repeat className="h-3.5 w-3.5 text-muted-foreground" aria-label="Agendamento recorrente" />
            )}
          </div>
          <p className="text-sm text-muted-foreground">
            {formatDateTime(appointment.start_at)} · {appointment.client?.name} (
            {appointment.client?.email})
            {appointment.staff && ` · ${appointment.staff.name}`}
          </p>
          {appointment.notes && (
            <p className="text-sm text-muted-foreground">Obs: {appointment.notes}</p>
          )}
        </div>

        {appointment.status === "pending" && (
          <div className="flex gap-2">
            <Button size="sm" onClick={() => onStatusChange("confirmed")}>
              <Check className="h-4 w-4" />
              Confirmar
            </Button>
            <Button variant="destructive" size="sm" onClick={() => onStatusChange("cancelled")}>
              <X className="h-4 w-4" />
              Cancelar
            </Button>
          </div>
        )}

        {appointment.status === "confirmed" && (
          <div className="flex gap-2">
            <Button size="sm" onClick={() => onStatusChange("completed")}>
              <CheckCheck className="h-4 w-4" />
              Concluir
            </Button>
            <Button variant="destructive" size="sm" onClick={() => onStatusChange("cancelled")}>
              <X className="h-4 w-4" />
              Cancelar
            </Button>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

function AgendamentosAdminPanel() {
  const [status, setStatus] = useState<AppointmentStatus | "">("");
  const [staffId, setStaffId] = useState("");
  const [range, setRange] = useState<{ from: string; to: string } | null>(null);
  const [selectedId, setSelectedId] = useState<number | null>(null);

  const { data: team } = useSWR("team", listTeam);

  const {
    data: appointments,
    isLoading,
    mutate: reloadAppointments,
  } = useSWR(
    range ? ["admin-appointments", range.from, range.to, status, staffId] : null,
    () =>
      adminAppointments({
        from: range!.from,
        to: range!.to,
        status: status || undefined,
        staffId: staffId ? Number(staffId) : undefined,
      })
  );

  const events: EventInput[] = useMemo(
    () =>
      (appointments ?? []).map((appointment) => ({
        id: String(appointment.id),
        title: appointment.service.name,
        start: appointment.start_at,
        end: appointment.end_at,
        backgroundColor: STATUS_COLOR[appointment.status].background,
        borderColor: STATUS_COLOR[appointment.status].background,
        textColor: STATUS_COLOR[appointment.status].text,
      })),
    [appointments]
  );

  const selectedAppointment = appointments?.find((appointment) => appointment.id === selectedId);

  function handleDatesSet(arg: DatesSetArg) {
    setRange({
      from: toLocalIsoDate(arg.start),
      to: toLocalIsoDate(arg.end),
    });
  }

  function handleEventClick(clickInfo: EventClickArg) {
    setSelectedId(Number(clickInfo.event.id));
  }

  async function handleStatusChange(appointment: Appointment, newStatus: AppointmentStatus) {
    if (newStatus === "cancelled" && !confirm(`Cancelar o agendamento de ${appointment.service.name}?`)) {
      return;
    }

    await updateAppointmentStatus(appointment.id, newStatus);
    reloadAppointments();
  }

  return (
    <div className="flex flex-col gap-6">
      <div className="flex flex-wrap gap-4">
        <div>
          <Label htmlFor="filter-status">Status</Label>
          <Select
            id="filter-status"
            className="w-48"
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

        {team && team.members.length > 1 && (
          <div>
            <Label htmlFor="filter-staff">Profissional</Label>
            <Select
              id="filter-staff"
              className="w-48"
              value={staffId}
              onChange={(e) => setStaffId(e.target.value)}
            >
              <option value="">Todos</option>
              {team.members.map((member) => (
                <option key={member.id} value={member.id}>
                  {member.name}
                </option>
              ))}
            </Select>
          </div>
        )}
      </div>

      <Card className="shadow-elevated-md">
        <CardContent className="p-5">
          <FullCalendar
            plugins={[dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin]}
            initialView="dayGridMonth"
            headerToolbar={{
              left: "prev,next today",
              center: "title",
              right: "dayGridMonth,timeGridWeek,listWeek",
            }}
            locale={ptBrLocale}
            height="auto"
            eventDisplay="block"
            events={events}
            eventClick={handleEventClick}
            datesSet={handleDatesSet}
            loading={(loading) => {
              if (loading) setSelectedId(null);
            }}
          />
        </CardContent>
      </Card>

      {isLoading && <Skeleton className="mx-auto h-4 w-48" />}

      {!isLoading && appointments?.length === 0 && (
        <EmptyState
          icon={MousePointerClick}
          title="Nenhum agendamento neste período"
          description="Navegue pelo calendário ou ajuste o filtro de status."
        />
      )}

      {selectedAppointment ? (
        <AppointmentDetail
          appointment={selectedAppointment}
          onStatusChange={(newStatus) => handleStatusChange(selectedAppointment, newStatus)}
        />
      ) : (
        appointments &&
        appointments.length > 0 && (
          <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
            <MousePointerClick className="h-4 w-4" />
            Clique em um agendamento no calendário pra ver os detalhes.
          </p>
        )
      )}
    </div>
  );
}

export default function AgendamentosAdminPage() {
  return (
    <RequireAuth role="admin">
      <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-10">
        <h1 className="mb-6 text-3xl font-bold tracking-tight text-foreground">Agendamentos</h1>
        <AgendamentosAdminPanel />
      </main>
    </RequireAuth>
  );
}
