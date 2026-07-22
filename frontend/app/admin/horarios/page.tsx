"use client";

import { useState, type FormEvent } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import useSWR from "swr";
import { CalendarOff, Trash2 } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Spinner } from "@/components/ui/spinner";
import {
  disconnectGoogleCalendar,
  getGoogleCalendarConnectUrl,
  getGoogleCalendarStatus,
} from "@/lib/api/google-calendar";
import {
  createScheduleBlock,
  deleteScheduleBlock,
  listBusinessHours,
  listScheduleBlocks,
  updateBusinessHours,
} from "@/lib/api/scheduling";
import type { BusinessHour } from "@/lib/types/scheduling";
import { formatApiError } from "@/lib/utils/format-error";

const DAY_NAMES = [
  "Domingo",
  "Segunda-feira",
  "Terça-feira",
  "Quarta-feira",
  "Quinta-feira",
  "Sexta-feira",
  "Sábado",
];

const emptyBlockForm = { date: "", allDay: true, start_time: "09:00", end_time: "18:00", reason: "" };

function BusinessHoursEditor({
  initialHours,
  onSaved,
}: {
  initialHours: BusinessHour[];
  onSaved: () => void;
}) {
  const [draft, setDraft] = useState<BusinessHour[]>(initialHours);
  const [error, setError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);

  function updateDay(dayOfWeek: number, changes: Partial<BusinessHour>) {
    setDraft((current) =>
      current.map((day) => (day.day_of_week === dayOfWeek ? { ...day, ...changes } : day))
    );
  }

  async function handleSave() {
    setError(null);
    setIsSaving(true);

    try {
      await updateBusinessHours(draft);
      onSaved();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <>
      {error && <Alert variant="destructive">{error}</Alert>}

      {draft.map((day) => (
        <div
          key={day.day_of_week}
          className="flex flex-wrap items-center gap-3 border-b border-border pb-3 last:border-0 last:pb-0"
        >
          <span className="w-36 shrink-0 text-sm font-medium text-foreground">
            {DAY_NAMES[day.day_of_week]}
          </span>

          <Button
            type="button"
            variant={day.is_open ? "secondary" : "outline"}
            size="sm"
            onClick={() => updateDay(day.day_of_week, { is_open: !day.is_open })}
          >
            {day.is_open ? "Aberto" : "Fechado"}
          </Button>

          {day.is_open && (
            <div className="flex items-center gap-2">
              <Input
                type="time"
                className="w-32"
                value={day.start_time ?? "09:00"}
                onChange={(e) => updateDay(day.day_of_week, { start_time: e.target.value })}
              />
              <span className="text-sm text-muted-foreground">até</span>
              <Input
                type="time"
                className="w-32"
                value={day.end_time ?? "18:00"}
                onChange={(e) => updateDay(day.day_of_week, { end_time: e.target.value })}
              />
            </div>
          )}
        </div>
      ))}

      <Button type="button" onClick={handleSave} disabled={isSaving} className="self-start">
        {isSaving ? "Salvando..." : "Salvar horários"}
      </Button>
    </>
  );
}

function BusinessHoursCard() {
  const { data: hours, isLoading, mutate: reloadHours } = useSWR("business-hours", listBusinessHours);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Horário de atendimento</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-col gap-4">
        {isLoading && (
          <div className="flex justify-center py-10">
            <Spinner className="h-6 w-6 text-muted-foreground" />
          </div>
        )}

        {hours && <BusinessHoursEditor initialHours={hours} onSaved={reloadHours} />}
      </CardContent>
    </Card>
  );
}

function GoogleCalendarCard() {
  const { data: connected, isLoading, mutate: reloadStatus } = useSWR(
    "google-calendar-status",
    getGoogleCalendarStatus
  );
  const [error, setError] = useState<string | null>(null);
  const [isWorking, setIsWorking] = useState(false);

  async function handleConnect() {
    setError(null);
    setIsWorking(true);

    try {
      const url = await getGoogleCalendarConnectUrl();
      window.location.href = url;
    } catch (err) {
      setError(formatApiError(err));
      setIsWorking(false);
    }
  }

  async function handleDisconnect() {
    setError(null);
    setIsWorking(true);

    try {
      await disconnectGoogleCalendar();
      reloadStatus();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsWorking(false);
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Google Calendar</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-col gap-4">
        {error && <Alert variant="destructive">{error}</Alert>}

        {isLoading ? (
          <div className="flex justify-center py-6">
            <Spinner className="h-6 w-6 text-muted-foreground" />
          </div>
        ) : (
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-center gap-2">
              <Badge variant={connected ? "success" : "secondary"}>
                {connected ? "Conectado" : "Não conectado"}
              </Badge>
              <p className="text-sm text-muted-foreground">
                {connected
                  ? "Agendamentos confirmados são criados no seu Google Calendar, e horários já ocupados na sua agenda pessoal bloqueiam o agendamento."
                  : "Conecte pra sincronizar os agendamentos confirmados com o seu Google Calendar."}
              </p>
            </div>

            {connected ? (
              <Button variant="destructive" size="sm" disabled={isWorking} onClick={handleDisconnect}>
                Desconectar
              </Button>
            ) : (
              <Button size="sm" disabled={isWorking} onClick={handleConnect}>
                Conectar
              </Button>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  );
}

function ScheduleBlocksCard() {
  const { data: blocks, isLoading, mutate: reloadBlocks } = useSWR("schedule-blocks", () =>
    listScheduleBlocks()
  );
  const [form, setForm] = useState(emptyBlockForm);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      await createScheduleBlock({
        date: form.date,
        start_time: form.allDay ? undefined : form.start_time,
        end_time: form.allDay ? undefined : form.end_time,
        reason: form.reason || undefined,
      });
      setForm(emptyBlockForm);
      reloadBlocks();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDelete(id: number) {
    await deleteScheduleBlock(id);
    reloadBlocks();
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Bloqueios de horário</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-col gap-6">
        <form onSubmit={handleSubmit} className="flex flex-col gap-4 rounded-lg border border-border p-4">
          {error && <Alert variant="destructive">{error}</Alert>}

          <div>
            <Label htmlFor="block-date">Data</Label>
            <Input
              id="block-date"
              type="date"
              required
              value={form.date}
              onChange={(e) => setForm({ ...form, date: e.target.value })}
            />
          </div>

          <label className="flex items-center gap-2 text-sm text-foreground">
            <input
              type="checkbox"
              checked={form.allDay}
              onChange={(e) => setForm({ ...form, allDay: e.target.checked })}
            />
            Bloquear o dia inteiro
          </label>

          {!form.allDay && (
            <div className="flex items-center gap-2">
              <Input
                type="time"
                className="w-32"
                value={form.start_time}
                onChange={(e) => setForm({ ...form, start_time: e.target.value })}
              />
              <span className="text-sm text-muted-foreground">até</span>
              <Input
                type="time"
                className="w-32"
                value={form.end_time}
                onChange={(e) => setForm({ ...form, end_time: e.target.value })}
              />
            </div>
          )}

          <div>
            <Label htmlFor="block-reason">Motivo (opcional)</Label>
            <Input
              id="block-reason"
              placeholder="Feriado, viagem, manutenção..."
              value={form.reason}
              onChange={(e) => setForm({ ...form, reason: e.target.value })}
            />
          </div>

          <Button type="submit" disabled={isSubmitting} className="self-start">
            Adicionar bloqueio
          </Button>
        </form>

        {isLoading && (
          <div className="flex justify-center py-10">
            <Spinner className="h-6 w-6 text-muted-foreground" />
          </div>
        )}

        {!isLoading && blocks?.length === 0 && (
          <EmptyState
            icon={CalendarOff}
            title="Nenhum bloqueio cadastrado"
            description="Use o formulário acima pra marcar um dia ou horário como indisponível."
          />
        )}

        <div className="flex flex-col gap-2">
          {blocks?.map((block) => (
            <div
              key={block.id}
              className="flex items-center justify-between gap-4 rounded-lg border border-border px-4 py-3"
            >
              <div>
                <p className="text-sm font-medium text-foreground">
                  {new Date(`${block.date}T00:00:00`).toLocaleDateString("pt-BR")}
                  {block.start_time && block.end_time
                    ? ` · ${block.start_time} às ${block.end_time}`
                    : " · dia inteiro"}
                </p>
                {block.reason && <p className="text-sm text-muted-foreground">{block.reason}</p>}
              </div>

              <Button variant="destructive" size="sm" onClick={() => handleDelete(block.id)}>
                <Trash2 className="h-4 w-4" />
                Remover
              </Button>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}

function GoogleCalendarStatusAlert() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const google = searchParams.get("google");

  if (!google) return null;

  function dismiss() {
    router.replace("/admin/horarios");
  }

  return (
    <Alert variant={google === "connected" ? "success" : "destructive"} className="flex items-center justify-between gap-4">
      <span>
        {google === "connected"
          ? "Google Calendar conectado com sucesso."
          : "Não foi possível conectar ao Google Calendar. Tente de novo."}
      </span>
      <Button variant="outline" size="sm" onClick={dismiss}>
        Fechar
      </Button>
    </Alert>
  );
}

export default function HorariosAdminPage() {
  return (
    <RequireAuth role="admin">
      <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 px-4 py-10">
        <h1 className="text-2xl font-semibold text-foreground">Horário de atendimento</h1>
        <GoogleCalendarStatusAlert />
        <GoogleCalendarCard />
        <BusinessHoursCard />
        <ScheduleBlocksCard />
      </main>
    </RequireAuth>
  );
}
