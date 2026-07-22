import { CalendarCheck2 } from "lucide-react";

import { ApiStatus } from "@/components/layout/api-status.component";

export function Footer() {
  return (
    <footer className="border-t border-border">
      <div className="mx-auto flex max-w-5xl flex-col items-center justify-between gap-3 px-4 py-6 text-sm text-muted-foreground sm:flex-row">
        <div className="flex items-center gap-2 font-medium text-foreground">
          <CalendarCheck2 className="h-4 w-4 text-primary" />
          Zelo
        </div>

        <ApiStatus />
      </div>
    </footer>
  );
}
