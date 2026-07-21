"use client";

import { useSyncExternalStore } from "react";
import { Moon, Sun } from "lucide-react";

import { applyTheme, getActiveTheme, subscribeTheme, type Theme } from "@/lib/utils/theme";

function getServerTheme(): Theme {
  return "light";
}

export function ThemeToggle() {
  const theme = useSyncExternalStore(subscribeTheme, getActiveTheme, getServerTheme);

  function toggle() {
    applyTheme(theme === "dark" ? "light" : "dark");
  }

  return (
    <button
      type="button"
      onClick={toggle}
      aria-label="Alternar tema claro/escuro"
      className="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
    >
      {theme === "dark" ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
    </button>
  );
}
