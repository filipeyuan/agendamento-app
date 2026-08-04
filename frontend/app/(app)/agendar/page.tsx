"use client";

import { Suspense, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { DayPicker } from "@daypicker/react";
import { ptBR } from "@daypicker/react/locale";
import { motion, useReducedMotion } from "motion/react";
import useSWR from "swr";
import { CalendarX, CreditCard, Store } from "lucide-react";

import { Alert } from "@/components/ui/alert";
import { Button, buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Label } from "@/components/ui/label";
import { Select } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { createAppointment } from "@/lib/api/appointments";
import { availableSlots as fetchAvailableSlots, listServices } from "@/lib/api/services";
import { cn } from "@/lib/utils/cn";
import { toLocalIsoDate, todayIsoDate } from "@/lib/utils/date";
import { formatApiError } from "@/lib/utils/format-error";
import { redirectTo } from "@/lib/utils/navigate";

function formatSlotTime(iso: string) {
  return new Date(iso).toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" });
}

function formatPrice(price: number) {
  return price.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

function AgendarForm() {
  const searchParams = useSearchParams();
  const business = searchParams.get("business");
  const shouldReduceMotion = useReducedMotion();

  const { data: services } = useSWR(business ? ["services", business] : null, () =>
    listServices(business!)
  );

  const [serviceIdOverride, setServiceIdOverride] = useState<string | null>(
    searchParams.get("service")
  );
  const serviceId = serviceIdOverride ?? (services?.[0] ? String(services[0].id) : "");

  const [date, setDate] = useState(todayIsoDate());
  const [staffId, setStaffId] = useState<string>("");
  const [selectedSlot, setSelectedSlot] = useState<string | null>(null);
  const [notes, setNotes] = useState("");
  const [recurringOccurrences, setRecurringOccurrences] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const selectedService = services?.find((service) => String(service.id) === serviceId);
  const requiresStaff = (selectedService?.staff.length ?? 0) > 0;

  const {
    data: slots,
    isLoading: isLoadingSlots,
    mutate: reloadSlots,
  } = useSWR(
    serviceId && date && (!requiresStaff || staffId) ? ["available-slots", serviceId, date, staffId] : null,
    () => fetchAvailableSlots(Number(serviceId), date, staffId ? Number(staffId) : undefined)
  );

  const validSelectedSlot = selectedSlot && slots?.includes(selectedSlot) ? selectedSlot : null;

  async function handleSubmit() {
    if (!validSelectedSlot) return;

    setIsSubmitting(true);
    setError(null);

    try {
      const { checkoutUrl } = await createAppointment({
        service_id: Number(serviceId),
        staff_id: staffId ? Number(staffId) : undefined,
        start_at: validSelectedSlot,
        notes: notes || undefined,
        recurring_occurrences: recurringOccurrences ? Number(recurringOccurrences) : undefined,
      });
      redirectTo(checkoutUrl);
    } catch (err) {
      setError(formatApiError(err));
      setSelectedSlot(null);
      reloadSlots();
    } finally {
      setIsSubmitting(false);
    }
  }

  if (!business) {
    return (
      <Card className="mx-auto w-full max-w-4xl">
        <CardContent className="py-10">
          <EmptyState
            icon={Store}
            title="Escolha um negócio pra agendar"
            description="Abra um negócio na lista de serviços e escolha o que quer agendar por lá."
          />
          <Link
            href="/servicos"
            className={cn(buttonVariants({ className: "mx-auto mt-4 w-fit" }))}
          >
            Ver negócios
          </Link>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="mx-auto w-full max-w-4xl">
      <CardHeader>
        <CardTitle>Agendar serviço</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-col gap-6">
        {error && <Alert variant="destructive">{error}</Alert>}
        {searchParams.get("payment") === "cancelled" && (
          <Alert variant="destructive">Pagamento cancelado. Escolha um horário e tente de novo.</Alert>
        )}

        <div>
          <Label htmlFor="service">Serviço</Label>
          <Select
            id="service"
            value={serviceId}
            onChange={(e) => {
              setServiceIdOverride(e.target.value);
              setStaffId("");
              setSelectedSlot(null);
            }}
          >
            {services?.map((service) => (
              <option key={service.id} value={service.id}>
                {service.name} ({service.duration_minutes} min) · {formatPrice(service.price)}
              </option>
            ))}
          </Select>
        </div>

        {requiresStaff && (
          <div>
            <Label htmlFor="staff">Profissional</Label>
            <Select
              id="staff"
              value={staffId}
              onChange={(e) => {
                setStaffId(e.target.value);
                setSelectedSlot(null);
              }}
            >
              <option value="">Escolha um profissional</option>
              {selectedService?.staff.map((member) => (
                <option key={member.id} value={member.id}>
                  {member.name}
                </option>
              ))}
            </Select>
          </div>
        )}

        <div className="grid gap-6 md:grid-cols-[19rem_1fr]">
          <div>
            <Label>Data</Label>
            <div className="shadow-elevated-md rounded-xl border border-border bg-card p-3">
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
          </div>

          <div className="flex flex-col gap-6">
            <div>
              <Label>Horário</Label>
              {requiresStaff && !staffId && (
                <p className="text-sm text-muted-foreground">Escolha um profissional pra ver os horários.</p>
              )}
              {isLoadingSlots && (
                <p className="text-sm text-muted-foreground">Carregando horários...</p>
              )}
              {!isLoadingSlots && slots?.length === 0 && (
                <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
                  <CalendarX className="h-4 w-4" />
                  Nenhum horário livre nesta data.
                </p>
              )}
              <div className="flex flex-wrap gap-2">
                {slots?.map((slot) => {
                  const isSelected = validSelectedSlot === slot;

                  return (
                    <motion.button
                      key={slot}
                      type="button"
                      onClick={() => setSelectedSlot(slot)}
                      whileHover={shouldReduceMotion ? undefined : { scale: 1.05 }}
                      whileTap={shouldReduceMotion ? undefined : { scale: 0.96 }}
                      className={cn(
                        "cursor-pointer rounded-full border px-4 py-1.5 text-sm font-medium transition-colors",
                        isSelected
                          ? "shadow-elevated-sm border-primary bg-primary text-primary-foreground"
                          : "border-primary/20 bg-primary/10 text-primary hover:bg-primary/20"
                      )}
                    >
                      {formatSlotTime(slot)}
                    </motion.button>
                  );
                })}
              </div>
            </div>

            <div>
              <Label htmlFor="notes">Observações (opcional)</Label>
              <Textarea id="notes" value={notes} onChange={(e) => setNotes(e.target.value)} />
            </div>

            <div>
              <Label htmlFor="recurring">Repetir semanalmente (opcional)</Label>
              <Select
                id="recurring"
                value={recurringOccurrences}
                onChange={(e) => setRecurringOccurrences(e.target.value)}
              >
                <option value="">Não repetir</option>
                <option value="4">Por 4 semanas</option>
                <option value="8">Por 8 semanas</option>
                <option value="12">Por 12 semanas</option>
              </Select>
              {recurringOccurrences && selectedService && (
                <p className="mt-1.5 text-sm text-muted-foreground">
                  {recurringOccurrences}x {formatPrice(selectedService.price)} · Total:{" "}
                  {formatPrice(selectedService.price * Number(recurringOccurrences))}
                </p>
              )}
            </div>

            <Button disabled={!validSelectedSlot || isSubmitting} onClick={handleSubmit}>
              <CreditCard className="h-4 w-4" />
              {isSubmitting ? "Redirecionando pro pagamento..." : "Pagar e agendar"}
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

export default function AgendarPage() {
  return (
    <main className="flex-1 px-4 py-10">
      <Suspense>
        <AgendarForm />
      </Suspense>
    </main>
  );
}
