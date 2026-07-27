"use client";

import { useState } from "react";
import { useSearchParams } from "next/navigation";
import { DayPicker } from "@daypicker/react";
import { ptBR } from "@daypicker/react/locale";
import useSWR from "swr";
import { CalendarClock, CalendarX2, Repeat, X } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Spinner } from "@/components/ui/spinner";
import { cancelAppointment, myAppointments, rescheduleAppointment } from "@/lib/api/appointments";
import { availableSlots as fetchAvailableSlots } from "@/lib/api/services";
import {
  APPOINTMENT_STATUS_BADGE_VARIANT,
  APPOINTMENT_STATUS_LABEL,
  PAYMENT_STATUS_BADGE_VARIANT,
  PAYMENT_STATUS_LABEL,
  type Appointment,
} from "@/lib/types/appointments";
import { cn } from "@/lib/utils/cn";
import { toLocalIsoDate, todayIsoDate } from "@/lib/utils/date";
import { formatApiError } from "@/lib/utils/format-error";

function formatDateTime(iso: string) {
  return new Date(iso).toLocaleString("pt-BR", {
    dateStyle: "short",
    timeStyle: "short",
  });
}

function formatSlotTime(iso: string) {
  return new Date(iso).toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" });
}

function PaymentStatusAlert() {
  const searchParams = useSearchParams();
  const payment = searchParams.get("payment");

  if (!payment) return null;

  return (
    <Alert variant={payment === "success" ? "success" : "destructive"} className="mb-6">
      {payment === "success"
        ? "Pagamento confirmado! Assim que o estabelecimento confirmar o agendamento, você recebe um e-mail."
        : "Pagamento cancelado. Se quiser tentar de novo, escolha um horário em Agendar."}
    </Alert>
  );
}

function RescheduleForm({ appointment, onDone }: { appointment: Appointment; onDone: () => void }) {
  const [date, setDate] = useState(todayIsoDate());
  const [selectedSlot, setSelectedSlot] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const { data: slots, isLoading } = useSWR(
    ["available-slots-reschedule", appointment.service.id, date],
    () => fetchAvailableSlots(appointment.service.id, date)
  );

  async function handleConfirm() {
    if (!selectedSlot) return;

    setIsSubmitting(true);
    setError(null);

    try {
      await rescheduleAppointment(appointment.id, selectedSlot);
      onDone();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <div className="mt-3 flex flex-col gap-3 rounded-lg border border-border bg-muted/40 p-3">
      {error && <Alert variant="destructive">{error}</Alert>}

      <div className="rounded-lg border border-border bg-card p-2">
        <DayPicker
          mode="single"
          locale={ptBR}
          selected={new Date(`${date}T00:00:00`)}
          onSelect={(selectedDate) => {
            if (!selectedDate) return;
            setDate(toLocalIsoDate(selectedDate));
            setSelectedSlot(null);
          }}
          disabled={{ before: new Date(new Date().setHours(0, 0, 0, 0)) }}
          showOutsideDays
        />
      </div>

      {isLoading && <p className="text-sm text-muted-foreground">Carregando horários...</p>}
      {!isLoading && slots?.length === 0 && (
        <p className="text-sm text-muted-foreground">Nenhum horário livre nesta data.</p>
      )}

      <div className="grid grid-cols-3 gap-2 sm:grid-cols-4">
        {slots?.map((slot) => (
          <button
            key={slot}
            type="button"
            onClick={() => setSelectedSlot(slot)}
            className={cn(
              "cursor-pointer rounded-md border border-border bg-card py-1.5 text-sm transition-colors hover:bg-accent",
              selectedSlot === slot && "border-primary bg-primary text-primary-foreground hover:opacity-90"
            )}
          >
            {formatSlotTime(slot)}
          </button>
        ))}
      </div>

      <div className="flex gap-2">
        <Button size="sm" disabled={!selectedSlot || isSubmitting} onClick={handleConfirm}>
          {isSubmitting ? "Remarcando..." : "Confirmar novo horário"}
        </Button>
        <Button size="sm" variant="secondary" onClick={onDone}>
          Cancelar
        </Button>
      </div>
    </div>
  );
}

function MeusAgendamentosList() {
  const { data: appointments, isLoading, mutate: reloadAppointments } = useSWR(
    "my-appointments",
    myAppointments
  );
  const [reschedulingId, setReschedulingId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function handleCancel(appointment: Appointment) {
    if (!confirm(`Cancelar o agendamento de ${appointment.service.name}?`)) return;

    setError(null);

    try {
      await cancelAppointment(appointment.id);
      reloadAppointments();
    } catch (err) {
      setError(formatApiError(err));
    }
  }

  if (isLoading) {
    return (
      <div className="flex justify-center py-16">
        <Spinner className="h-6 w-6 text-muted-foreground" />
      </div>
    );
  }

  if (!appointments || appointments.length === 0) {
    return (
      <EmptyState
        icon={CalendarX2}
        title="Você ainda não tem agendamentos"
        description="Escolha um serviço e marque um horário pra começar."
      />
    );
  }

  return (
    <div className="flex flex-col gap-3">
      {error && <Alert variant="destructive">{error}</Alert>}

      {appointments.map((appointment) => {
        const canManage = appointment.status === "pending" || appointment.status === "confirmed";

        return (
          <Card key={appointment.id}>
            <CardHeader className="flex-row items-center justify-between">
              <CardTitle className="flex items-center gap-1.5 text-base">
                {appointment.service.name}
                {appointment.recurring_group_id && (
                  <Repeat className="h-3.5 w-3.5 text-muted-foreground" aria-label="Agendamento recorrente" />
                )}
              </CardTitle>
              <div className="flex items-center gap-2">
                <Badge variant={PAYMENT_STATUS_BADGE_VARIANT[appointment.payment_status]}>
                  {PAYMENT_STATUS_LABEL[appointment.payment_status]}
                </Badge>
                <Badge variant={APPOINTMENT_STATUS_BADGE_VARIANT[appointment.status]}>
                  {APPOINTMENT_STATUS_LABEL[appointment.status]}
                </Badge>
              </div>
            </CardHeader>
            <CardContent className="flex flex-col gap-1 text-sm text-muted-foreground">
              <span>{formatDateTime(appointment.start_at)}</span>
              {appointment.notes && <span>Observações: {appointment.notes}</span>}

              {canManage && (
                <div className="mt-2 flex gap-2">
                  <Button
                    size="sm"
                    variant="secondary"
                    onClick={() =>
                      setReschedulingId(reschedulingId === appointment.id ? null : appointment.id)
                    }
                  >
                    <CalendarClock className="h-4 w-4" />
                    Remarcar
                  </Button>
                  <Button size="sm" variant="destructive" onClick={() => handleCancel(appointment)}>
                    <X className="h-4 w-4" />
                    Cancelar
                  </Button>
                </div>
              )}

              {reschedulingId === appointment.id && (
                <RescheduleForm
                  appointment={appointment}
                  onDone={() => {
                    setReschedulingId(null);
                    reloadAppointments();
                  }}
                />
              )}
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
}

export default function MeusAgendamentosPage() {
  return (
    <RequireAuth>
      <main className="mx-auto w-full max-w-3xl flex-1 px-4 py-10">
        <h1 className="mb-6 text-2xl font-semibold text-foreground">Meus agendamentos</h1>
        <PaymentStatusAlert />
        <MeusAgendamentosList />
      </main>
    </RequireAuth>
  );
}
