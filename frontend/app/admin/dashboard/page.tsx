"use client";

import { useMemo, useState } from "react";
import useSWR from "swr";
import { CalendarCheck2, DollarSign, TrendingDown, Users, type LucideIcon } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Alert } from "@/components/ui/alert";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select } from "@/components/ui/select";
import { Spinner } from "@/components/ui/spinner";
import { getAnalyticsSummary } from "@/lib/api/analytics";
import type { AnalyticsSummary } from "@/lib/types/analytics";
import { APPOINTMENT_STATUS_LABEL, type AppointmentStatus } from "@/lib/types/appointments";
import { formatApiError } from "@/lib/utils/format-error";

const STATUS_ORDER: AppointmentStatus[] = ["pending", "confirmed", "completed", "cancelled"];

const STATUS_BAR_COLOR: Record<AppointmentStatus, string> = {
  pending: "var(--warning)",
  confirmed: "var(--success)",
  completed: "var(--primary)",
  cancelled: "var(--destructive)",
};

function formatCurrency(value: number) {
  return value.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

function StatTile({ icon: Icon, label, value }: { icon: LucideIcon; label: string; value: string }) {
  return (
    <Card>
      <CardContent className="flex items-center gap-3 py-5">
        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
          <Icon className="h-5 w-5 text-primary" />
        </div>
        <div className="min-w-0">
          <p className="break-words text-xl font-semibold text-foreground">{value}</p>
          <p className="text-sm text-muted-foreground">{label}</p>
        </div>
      </CardContent>
    </Card>
  );
}

function TrendChart({ data }: { data: AnalyticsSummary["by_day"] }) {
  const [hovered, setHovered] = useState<number | null>(null);
  const max = Math.max(1, ...data.map((d) => d.count));

  return (
    <div className="flex items-end gap-1" style={{ height: 160 }}>
      {data.map((day, index) => {
        const heightPct = (day.count / max) * 100;

        return (
          <div
            key={day.date}
            className="group relative flex h-full flex-1 flex-col items-center justify-end"
            onMouseEnter={() => setHovered(index)}
            onMouseLeave={() => setHovered((current) => (current === index ? null : current))}
          >
            {hovered === index && (
              <div className="absolute -top-9 z-10 whitespace-nowrap rounded-md border border-border bg-card px-2 py-1 text-xs text-foreground shadow-sm">
                {new Date(`${day.date}T00:00:00`).toLocaleDateString("pt-BR", {
                  day: "2-digit",
                  month: "2-digit",
                })}
                {" · "}
                {day.count} {day.count === 1 ? "agendamento" : "agendamentos"}
              </div>
            )}
            <div
              className="w-full rounded-t-sm bg-primary transition-opacity group-hover:opacity-80"
              style={{ height: `${Math.max(heightPct, 2)}%` }}
            />
          </div>
        );
      })}
    </div>
  );
}

function StatusBreakdown({ byStatus }: { byStatus: AnalyticsSummary["by_status"] }) {
  const total = STATUS_ORDER.reduce((sum, status) => sum + byStatus[status], 0);

  return (
    <div className="flex flex-col gap-3">
      <div className="flex h-3 w-full overflow-hidden rounded-full bg-muted">
        {total === 0 ? null : (
          STATUS_ORDER.map((status) => {
            const count = byStatus[status];
            if (!count) return null;
            return (
              <div
                key={status}
                style={{ width: `${(count / total) * 100}%`, backgroundColor: STATUS_BAR_COLOR[status] }}
                className="h-full"
              />
            );
          })
        )}
      </div>

      <div className="flex flex-wrap gap-x-4 gap-y-1">
        {STATUS_ORDER.map((status) => (
          <div key={status} className="flex items-center gap-1.5 text-sm">
            <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: STATUS_BAR_COLOR[status] }} />
            <span className="text-foreground">{APPOINTMENT_STATUS_LABEL[status]}</span>
            <span className="text-muted-foreground">({byStatus[status]})</span>
          </div>
        ))}
      </div>
    </div>
  );
}

function TopServices({ services }: { services: AnalyticsSummary["top_services"] }) {
  const max = Math.max(1, ...services.map((s) => s.count));

  return (
    <div className="flex flex-col gap-3">
      {services.map((service) => (
        <div key={service.name} className="flex items-center gap-3">
          <span className="w-32 shrink-0 truncate text-sm text-foreground">{service.name}</span>
          <div className="h-2 flex-1 overflow-hidden rounded-full bg-muted">
            <div className="h-full rounded-full bg-primary" style={{ width: `${(service.count / max) * 100}%` }} />
          </div>
          <span className="w-6 shrink-0 text-right text-sm text-muted-foreground">{service.count}</span>
        </div>
      ))}
    </div>
  );
}

function DashboardPanel() {
  const [days, setDays] = useState(30);
  const { data, error, isLoading } = useSWR(["analytics", days], () => getAnalyticsSummary(days));

  const totalAppointments = useMemo(
    () => (data ? Object.values(data.by_status).reduce((sum, n) => sum + n, 0) : 0),
    [data]
  );

  const cancelledRate = useMemo(() => {
    if (!data || totalAppointments === 0) return 0;
    return Math.round((data.by_status.cancelled / totalAppointments) * 100);
  }, [data, totalAppointments]);

  return (
    <div className="flex flex-col gap-6">
      <div className="flex justify-end">
        <Select
          value={String(days)}
          onChange={(e) => setDays(Number(e.target.value))}
          className="w-40"
        >
          <option value={7}>Últimos 7 dias</option>
          <option value={30}>Últimos 30 dias</option>
          <option value={90}>Últimos 90 dias</option>
        </Select>
      </div>

      {isLoading && (
        <div className="flex justify-center py-16">
          <Spinner className="h-6 w-6 text-muted-foreground" />
        </div>
      )}

      {error && !isLoading && (
        <Alert variant="destructive">
          {formatApiError(error)} Se você entrou há um tempo, tente sair e entrar de novo.
        </Alert>
      )}

      {data && (
        <>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatTile icon={CalendarCheck2} label="Agendamentos no período" value={String(totalAppointments)} />
            <StatTile
              icon={DollarSign}
              label="Receita (confirmados + concluídos)"
              value={formatCurrency(data.revenue)}
            />
            <StatTile icon={TrendingDown} label="Taxa de cancelamento" value={`${cancelledRate}%`} />
            <StatTile icon={Users} label="Serviço mais agendado" value={data.top_services[0]?.name ?? "-"} />
          </div>

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Agendamentos por dia</CardTitle>
            </CardHeader>
            <CardContent>
              <TrendChart data={data.by_day} />
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Agendamentos por status</CardTitle>
            </CardHeader>
            <CardContent>
              <StatusBreakdown byStatus={data.by_status} />
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Serviços mais agendados</CardTitle>
            </CardHeader>
            <CardContent>
              {data.top_services.length === 0 ? (
                <p className="text-sm text-muted-foreground">Nenhum agendamento no período.</p>
              ) : (
                <TopServices services={data.top_services} />
              )}
            </CardContent>
          </Card>
        </>
      )}
    </div>
  );
}

export default function DashboardAdminPage() {
  return (
    <RequireAuth role="admin">
      <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-10">
        <h1 className="mb-6 text-2xl font-semibold text-foreground">Dashboard</h1>
        <DashboardPanel />
      </main>
    </RequireAuth>
  );
}
