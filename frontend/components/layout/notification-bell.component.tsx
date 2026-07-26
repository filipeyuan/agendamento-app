"use client";

import { useEffect, useRef, useState } from "react";
import useSWR from "swr";
import { Bell, Calendar, CalendarX2, CheckCheck, CreditCard, type LucideIcon } from "lucide-react";

import { listNotifications, markAllNotificationsRead, markNotificationRead } from "@/lib/api/notifications";
import { useAuth } from "@/lib/auth/context";
import type { AppNotification, NotificationType } from "@/lib/types/notifications";
import { cn } from "@/lib/utils/cn";

const TYPE_ICON: Record<NotificationType, LucideIcon> = {
  payment_confirmed: CreditCard,
  appointment_confirmed: Calendar,
  appointment_cancelled: CalendarX2,
};

function formatRelativeTime(iso: string) {
  const minutes = Math.round((Date.now() - new Date(iso).getTime()) / 60000);

  if (minutes < 1) return "agora";
  if (minutes < 60) return `há ${minutes} min`;

  const hours = Math.round(minutes / 60);
  if (hours < 24) return `há ${hours}h`;

  return `há ${Math.round(hours / 24)}d`;
}

export function NotificationBell() {
  const { user } = useAuth();
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  const { data, mutate } = useSWR(user ? "notifications" : null, listNotifications, {
    refreshInterval: 20000,
  });

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  if (!user) return null;

  const notifications = data?.data ?? [];
  const unreadCount = data?.unread_count ?? 0;

  async function handleNotificationClick(notification: AppNotification) {
    if (notification.read_at) return;
    await markNotificationRead(notification.id);
    mutate();
  }

  async function handleMarkAllRead() {
    await markAllNotificationsRead();
    mutate();
  }

  return (
    <div ref={containerRef} className="relative">
      <button
        type="button"
        onClick={() => setIsOpen((open) => !open)}
        aria-label="Notificações"
        className="relative inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
      >
        <Bell className="h-4 w-4" />
        {unreadCount > 0 && (
          <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-destructive" />
        )}
      </button>

      {isOpen && (
        <div className="absolute right-0 top-11 z-20 w-80 rounded-lg border border-border bg-card shadow-lg">
          <div className="flex items-center justify-between border-b border-border px-4 py-2.5">
            <span className="text-sm font-medium text-foreground">Notificações</span>
            {unreadCount > 0 && (
              <button
                type="button"
                onClick={handleMarkAllRead}
                className="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
              >
                <CheckCheck className="h-3.5 w-3.5" />
                Marcar todas como lidas
              </button>
            )}
          </div>

          <div className="max-h-80 overflow-y-auto">
            {notifications.length === 0 && (
              <p className="px-4 py-6 text-center text-sm text-muted-foreground">
                Nenhuma notificação por aqui ainda.
              </p>
            )}

            {notifications.map((notification) => {
              const Icon = notification.type ? TYPE_ICON[notification.type] : Bell;

              return (
                <button
                  key={notification.id}
                  type="button"
                  onClick={() => handleNotificationClick(notification)}
                  className={cn(
                    "flex w-full items-start gap-3 border-b border-border px-4 py-3 text-left last:border-0 hover:bg-accent",
                    !notification.read_at && "bg-primary/5"
                  )}
                >
                  <Icon className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                  <div className="flex flex-col gap-0.5">
                    <span className="text-sm text-foreground">{notification.message}</span>
                    <span className="text-xs text-muted-foreground">
                      {formatRelativeTime(notification.created_at)}
                    </span>
                  </div>
                  {!notification.read_at && (
                    <span className="ml-auto mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                  )}
                </button>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
