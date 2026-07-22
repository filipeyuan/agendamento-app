import Link from "next/link";
import { CalendarCheck2, ExternalLink } from "lucide-react";

export function Footer() {
  return (
    <footer className="border-t border-border">
      <div className="mx-auto flex max-w-5xl flex-col items-center justify-between gap-3 px-4 py-6 text-sm text-muted-foreground sm:flex-row">
        <div className="flex items-center gap-2 font-medium text-foreground">
          <CalendarCheck2 className="h-4 w-4 text-primary" />
          Zelo
        </div>

        <Link
          href="https://github.com/filipeyuan/agendamento-app"
          target="_blank"
          rel="noopener noreferrer"
          className="flex items-center gap-1.5 transition-colors hover:text-foreground"
        >
          Código no GitHub
          <ExternalLink className="h-3.5 w-3.5" />
        </Link>
      </div>
    </footer>
  );
}
