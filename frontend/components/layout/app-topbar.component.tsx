"use client";

import { Menu } from "lucide-react";

import { NotificationBell } from "@/components/layout/notification-bell.component";

export function AppTopBar({ onMenuClick }: { onMenuClick: () => void }) {
  return (
    <header className="sticky top-0 z-10 flex h-14 items-center justify-between border-b border-border bg-background/80 px-4 backdrop-blur md:justify-end">
      <button
        type="button"
        onClick={onMenuClick}
        aria-label="Abrir menu"
        className="flex h-9 w-9 items-center justify-center rounded-md text-foreground hover:bg-accent md:hidden"
      >
        <Menu className="h-5 w-5" />
      </button>

      <NotificationBell />
    </header>
  );
}
