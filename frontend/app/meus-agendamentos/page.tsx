"use client";

import { useSearchParams } from "next/navigation";
import useSWR from "swr";
import { CalendarX2 } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Spinner } from "@/components/ui/spinner";
import { myAppointments } from "@/lib/api/appointments";
import {
  APPOINTMENT_STATUS_BADGE_VARIANT,
  APPOINTMENT_STATUS_LABEL,
  PAYMENT_STATUS_BADGE_VARIANT,
  PAYMENT_STATUS_LABEL,
} from "@/lib/types/appointments";

function formatDateTime(iso: string) {
  return new Date(iso).toLocaleString("pt-BR", {
    dateStyle: "short",
    timeStyle: "short",
  });
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

function MeusAgendamentosList() {
  const { data: appointments, isLoading } = useSWR("my-appointments", myAppointments);

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
      {appointments.map((appointment) => (
        <Card key={appointment.id}>
          <CardHeader className="flex-row items-center justify-between">
            <CardTitle className="text-base">{appointment.service.name}</CardTitle>
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
          </CardContent>
        </Card>
      ))}
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
