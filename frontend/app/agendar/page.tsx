"use client";

import { Suspense, useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { createAppointment } from "@/lib/api/appointments";
import { availableSlots as fetchAvailableSlots, listServices } from "@/lib/api/services";
import type { Service } from "@/lib/types/services";
import { cn } from "@/lib/utils/cn";
import { todayIsoDate } from "@/lib/utils/date";
import { formatApiError } from "@/lib/utils/format-error";

function formatSlotTime(iso: string) {
  return new Date(iso).toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" });
}

function AgendarForm() {
  const searchParams = useSearchParams();
  const router = useRouter();

  const [services, setServices] = useState<Service[]>([]);
  const [serviceId, setServiceId] = useState<string>(searchParams.get("service") ?? "");
  const [date, setDate] = useState(todayIsoDate());
  const [slots, setSlots] = useState<string[]>([]);
  const [selectedSlot, setSelectedSlot] = useState<string | null>(null);
  const [notes, setNotes] = useState("");
  const [isLoadingSlots, setIsLoadingSlots] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    listServices().then((data) => {
      setServices(data);
      if (!serviceId && data.length > 0) {
        setServiceId(String(data[0].id));
      }
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (!serviceId || !date) return;

    setIsLoadingSlots(true);
    setSelectedSlot(null);
    setError(null);

    fetchAvailableSlots(Number(serviceId), date)
      .then(setSlots)
      .catch(() => setSlots([]))
      .finally(() => setIsLoadingSlots(false));
  }, [serviceId, date]);

  async function handleSubmit() {
    if (!selectedSlot) return;

    setIsSubmitting(true);
    setError(null);

    try {
      await createAppointment({
        service_id: Number(serviceId),
        start_at: selectedSlot,
        notes: notes || undefined,
      });
      router.push("/meus-agendamentos");
    } catch (err) {
      setError(formatApiError(err));
      fetchAvailableSlots(Number(serviceId), date).then(setSlots);
      setSelectedSlot(null);
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

        <div className="grid gap-4 sm:grid-cols-2">
          <div>
            <Label htmlFor="service">Serviço</Label>
            <Select id="service" value={serviceId} onChange={(e) => setServiceId(e.target.value)}>
              {services.map((service) => (
                <option key={service.id} value={service.id}>
                  {service.name} ({service.duration_minutes} min)
                </option>
              ))}
            </Select>
          </div>

          <div>
            <Label htmlFor="date">Data</Label>
            <Input
              id="date"
              type="date"
              min={todayIsoDate()}
              value={date}
              onChange={(e) => setDate(e.target.value)}
            />
          </div>
        </div>

        <div>
          <Label>Horário</Label>
          {isLoadingSlots && <p className="text-sm text-muted-foreground">Carregando horários...</p>}
          {!isLoadingSlots && slots.length === 0 && (
            <p className="text-sm text-muted-foreground">Nenhum horário livre nesta data.</p>
          )}
          <div className="flex flex-wrap gap-2">
            {slots.map((slot) => (
              <button
                key={slot}
                type="button"
                onClick={() => setSelectedSlot(slot)}
                className={cn(
                  "rounded-md border border-border px-3 py-1.5 text-sm transition-colors hover:bg-accent",
                  selectedSlot === slot && "border-primary bg-primary text-primary-foreground hover:opacity-90"
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

        <Button disabled={!selectedSlot || isSubmitting} onClick={handleSubmit}>
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
