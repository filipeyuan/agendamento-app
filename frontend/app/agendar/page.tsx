"use client";

import { Suspense, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import type { DayCellContentArg } from "@fullcalendar/core";
import ptBrLocale from "@fullcalendar/core/locales/pt-br";
import dayGridPlugin from "@fullcalendar/daygrid";
import type { DateClickArg } from "@fullcalendar/interaction";
import interactionPlugin from "@fullcalendar/interaction";
import FullCalendar from "@fullcalendar/react";
import useSWR from "swr";
import { CalendarCheck, CalendarX } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Select } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { createAppointment } from "@/lib/api/appointments";
import { availableSlots as fetchAvailableSlots, listServices } from "@/lib/api/services";
import { cn } from "@/lib/utils/cn";
import { toLocalIsoDate, todayIsoDate } from "@/lib/utils/date";
import { formatApiError } from "@/lib/utils/format-error";

function formatSlotTime(iso: string) {
  return new Date(iso).toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" });
}

function AgendarForm() {
  const searchParams = useSearchParams();
  const router = useRouter();

  const { data: services } = useSWR("services", listServices);

  const [serviceIdOverride, setServiceIdOverride] = useState<string | null>(
    searchParams.get("service")
  );
  const serviceId = serviceIdOverride ?? (services?.[0] ? String(services[0].id) : "");

  const [date, setDate] = useState(todayIsoDate());
  const [selectedSlot, setSelectedSlot] = useState<string | null>(null);
  const [notes, setNotes] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const {
    data: slots,
    isLoading: isLoadingSlots,
    mutate: reloadSlots,
  } = useSWR(
    serviceId && date ? ["available-slots", serviceId, date] : null,
    () => fetchAvailableSlots(Number(serviceId), date)
  );

  const validSelectedSlot = selectedSlot && slots?.includes(selectedSlot) ? selectedSlot : null;

  async function handleSubmit() {
    if (!validSelectedSlot) return;

    setIsSubmitting(true);
    setError(null);

    try {
      await createAppointment({
        service_id: Number(serviceId),
        start_at: validSelectedSlot,
        notes: notes || undefined,
      });
      router.push("/meus-agendamentos");
    } catch (err) {
      setError(formatApiError(err));
      setSelectedSlot(null);
      reloadSlots();
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <Card className="mx-auto w-full max-w-2xl">
      <CardHeader>
        <CardTitle>Agendar serviço</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-col gap-4">
        {error && <Alert variant="destructive">{error}</Alert>}

        <div>
          <Label htmlFor="service">Serviço</Label>
          <Select
            id="service"
            value={serviceId}
            onChange={(e) => {
              setServiceIdOverride(e.target.value);
              setSelectedSlot(null);
            }}
          >
            {services?.map((service) => (
              <option key={service.id} value={service.id}>
                {service.name} ({service.duration_minutes} min)
              </option>
            ))}
          </Select>
        </div>

        <div>
          <Label>Data</Label>
          <div className="rounded-md border border-border p-2">
            <FullCalendar
              plugins={[dayGridPlugin, interactionPlugin]}
              initialView="dayGridMonth"
              headerToolbar={{ left: "prev,next today", center: "title", right: "" }}
              locale={ptBrLocale}
              height="auto"
              validRange={{ start: todayIsoDate() }}
              dateClick={(arg: DateClickArg) => {
                setDate(toLocalIsoDate(arg.date));
                setSelectedSlot(null);
              }}
              dayCellClassNames={(arg: DayCellContentArg) =>
                toLocalIsoDate(arg.date) === date ? ["selected-date-cell"] : []
              }
            />
          </div>
        </div>

        <div>
          <Label>Horário</Label>
          {isLoadingSlots && <p className="text-sm text-muted-foreground">Carregando horários...</p>}
          {!isLoadingSlots && slots?.length === 0 && (
            <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
              <CalendarX className="h-4 w-4" />
              Nenhum horário livre nesta data.
            </p>
          )}
          <div className="flex flex-wrap gap-2">
            {slots?.map((slot) => (
              <button
                key={slot}
                type="button"
                onClick={() => setSelectedSlot(slot)}
                className={cn(
                  "cursor-pointer rounded-md border border-border px-3 py-1.5 text-sm transition-colors hover:bg-accent",
                  validSelectedSlot === slot &&
                    "border-primary bg-primary text-primary-foreground hover:opacity-90"
                )}
              >
                {formatSlotTime(slot)}
              </button>
            ))}
          </div>
        </div>

        <div>
          <Label htmlFor="notes">Observações (opcional)</Label>
          <Textarea id="notes" value={notes} onChange={(e) => setNotes(e.target.value)} />
        </div>

        <Button disabled={!validSelectedSlot || isSubmitting} onClick={handleSubmit}>
          <CalendarCheck className="h-4 w-4" />
          {isSubmitting ? "Agendando..." : "Confirmar agendamento"}
        </Button>
      </CardContent>
    </Card>
  );
}

export default function AgendarPage() {
  return (
    <RequireAuth>
      <main className="flex-1 px-4 py-10">
        <Suspense>
          <AgendarForm />
        </Suspense>
      </main>
    </RequireAuth>
  );
}
