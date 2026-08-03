"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import useSWR from "swr";
import { CalendarCheck2, DollarSign, Lock, Users, type LucideIcon } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Alert } from "@/components/ui/alert";
import { buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import { Spinner } from "@/components/ui/spinner";
import { getAnalyticsSummary } from "@/lib/api/analytics";
import { useAuth } from "@/lib/auth/context";
import type { AnalyticsSummary } from "@/lib/types/analytics";
import { APPOINTMENT_STATUS_LABEL, type AppointmentStatus } from "@/lib/types/appointments";
import { cn } from "@/lib/utils/cn";
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

const STAT_TINT: Record<string, string> = {
  primary: "bg-primary/12 text-primary",
  success: "bg-success/12 text-success",
  warning: "bg-warning/15 text-warning",
  destructive: "bg-destructive/12 text-destructive",
};

function StatTile({
  icon: Icon,
  label,
  value,
  tint,
}: {
  icon: LucideIcon;
  label: string;
  value: string;
  tint: keyof typeof STAT_TINT;
}) {
  return (
    <Card>
      <CardContent className="flex items-center gap-4 py-6">
        <div className={cn("flex h-12 w-12 shrink-0 items-center justify-center rounded-full", STAT_TINT[tint])}>
          <Icon className="h-5 w-5" />
        </div>
        <div className="min-w-0">
          <p className="break-words text-2xl font-bold tracking-tight text-foreground">{value}</p>
          <p className="text-sm text-muted-foreground">{label}</p>
        </div>
      </CardContent>
    </Card>
  );
}

function DonutRing({
  percentage,
  color,
  size = 96,
  strokeWidth = 10,
}: {
  percentage: number;
  color: string;
  size?: number;
  strokeWidth?: number;
}) {
  const radius = (size - strokeWidth) / 2;
  const circumference = 2 * Math.PI * radius;
  const offset = circumference - (Math.min(Math.max(percentage, 0), 100) / 100) * circumference;

  return (
    <div className="relative shrink-0" style={{ width: size, height: size }}>
      <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`} className="-rotate-90">
        <circle cx={size / 2} cy={size / 2} r={radius} fill="none" stroke="var(--muted)" strokeWidth={strokeWidth} />
        <circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          fill="none"
          stroke={color}
          strokeWidth={strokeWidth}
          strokeDasharray={circumference}
          strokeDashoffset={offset}
          strokeLinecap="round"
          style={{ transition: "stroke-dashoffset 0.6s ease" }}
        />
      </svg>
      <div className="absolute inset-0 flex items-center justify-center text-lg font-bold text-foreground">
        {Math.round(percentage)}%
      </div>
    </div>
  );
}

function RateTile({
  label,
  detail,
  percentage,
  color,
}: {
  label: string;
  detail: string;
  percentage: number;
  color: string;
}) {
  return (
    <Card>
      <CardContent className="flex items-center gap-4 py-6">
        <DonutRing percentage={percentage} color={color} size={56} strokeWidth={6} />
        <div className="min-w-0">
          <p className="text-sm font-medium text-foreground">{label}</p>
          <p className="break-words text-sm text-muted-foreground">{detail}</p>
        </div>
      </CardContent>
    </Card>
  );
}

function TrendChart({ data }: { data: AnalyticsSummary["by_day"] }) {
  const [hovered, setHovered] = useState<number | null>(null);
  const max = Math.max(1, ...data.map((d) => d.count));

  return (
    <div className="flex items-end gap-1.5" style={{ height: 180 }}>
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
              <div className="shadow-elevated-md absolute -top-9 z-10 whitespace-nowrap rounded-lg border border-border bg-card px-2.5 py-1.5 text-xs font-medium text-foreground">
                {new Date(`${day.date}T00:00:00`).toLocaleDateString("pt-BR", {
                  day: "2-digit",
                  month: "2-digit",
                })}
                {" · "}
                {day.count} {day.count === 1 ? "agendamento" : "agendamentos"}
              </div>
            )}
            <div
              className="w-full rounded-t-full transition-opacity group-hover:opacity-80"
              style={{
                height: `${Math.max(heightPct, 4)}%`,
                backgroundImage: "linear-gradient(180deg, var(--primary), color-mix(in oklch, var(--primary) 55%, transparent))",
              }}
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

function DashboardSkeleton() {
  return (
    <>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {Array.from({ length: 4 }).map((_, index) => (
          <Card key={index}>
            <CardContent className="flex items-center gap-4 py-6">
              <Skeleton className="h-12 w-12 shrink-0 rounded-full" />
              <div className="flex min-w-0 flex-1 flex-col gap-2">
                <Skeleton className="h-6 w-20" />
                <Skeleton className="h-3.5 w-28" />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <Card>
        <CardHeader>
          <Skeleton className="h-5 w-40" />
        </CardHeader>
        <CardContent>
          <Skeleton className="h-[180px] w-full" />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <Skeleton className="h-5 w-44" />
        </CardHeader>
        <CardContent className="flex flex-col gap-3">
          <Skeleton className="h-3 w-full rounded-full" />
          <div className="flex gap-4">
            <Skeleton className="h-4 w-20" />
            <Skeleton className="h-4 w-20" />
            <Skeleton className="h-4 w-20" />
          </div>
        </CardContent>
      </Card>
    </>
  );
}

function ProLockedOverlay() {
  return (
    <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 rounded-b-2xl bg-card/70 backdrop-blur-[1px]">
      <span className="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary">
        <Lock className="h-4 w-4" />
      </span>
      <p className="text-sm font-medium text-foreground">Insight do plano Pro</p>
      <Link href="/admin/plano" className={cn(buttonVariants({ size: "sm" }), "rounded-full")}>
        Assinar Pro
      </Link>
    </div>
  );
}

function DashboardPanel() {
  const { user } = useAuth();
  const isPro = user?.business?.plan === "pro";
  const [days, setDays] = useState(30);
  const [failedAttempts, setFailedAttempts] = useState(0);
  const { data, error, isLoading } = useSWR(["analytics", days], () => getAnalyticsSummary(days), {
    onErrorRetry: (_err, _key, _config, revalidate, opts) => {
      setFailedAttempts(opts.retryCount + 1);
      if (opts.retryCount >= 8) return;
      setTimeout(() => revalidate(opts), Math.min(3000 * (opts.retryCount + 1), 10000));
    },
    onSuccess: () => setFailedAttempts(0),
  });

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

      {isLoading && <DashboardSkeleton />}

      {error && !isLoading && failedAttempts <= 3 && (
        <div className="flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
          <Spinner className="h-4 w-4" />
          Conectando ao servidor, isso pode levar até 30 segundos na primeira vez do dia...
        </div>
      )}

      {error && !isLoading && failedAttempts > 3 && (
        <Alert variant="destructive">
          {formatApiError(error)} Se você entrou há um tempo, tente sair e entrar de novo.
        </Alert>
      )}

      {data && (
        <>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatTile
              icon={CalendarCheck2}
              label="Agendamentos no período"
              value={String(totalAppointments)}
              tint="primary"
            />
            <StatTile
              icon={DollarSign}
              label="Receita (confirmados + concluídos)"
              value={formatCurrency(data.revenue)}
              tint="success"
            />
            <RateTile
              label="Taxa de cancelamento"
              detail={`${data.by_status.cancelled} de ${totalAppointments} agendamentos`}
              percentage={cancelledRate}
              color="var(--destructive)"
            />
            <StatTile
              icon={Users}
              label="Serviço mais agendado"
              value={data.top_services[0]?.name ?? "-"}
              tint="warning"
            />
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

          <Card className="relative overflow-hidden">
            <CardHeader>
              <CardTitle className="text-base">Serviços mais agendados</CardTitle>
            </CardHeader>
            <CardContent className={cn(!isPro && "pointer-events-none select-none blur-sm")}>
              {data.top_services.length === 0 ? (
                <p className="text-sm text-muted-foreground">Nenhum agendamento no período.</p>
              ) : (
                <TopServices services={data.top_services} />
              )}
            </CardContent>
            {!isPro && <ProLockedOverlay />}
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
        <h1 className="mb-6 text-3xl font-bold tracking-tight text-foreground">Dashboard</h1>
        <DashboardPanel />
      </main>
    </RequireAuth>
  );
}
