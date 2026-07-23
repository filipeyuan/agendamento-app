import Link from "next/link";
import { Code2 } from "lucide-react";

import { ApiStatus } from "@/components/layout/api-status.component";
import { Logo } from "@/components/layout/logo.component";
import { API_URL } from "@/lib/api/client";

const apiDocsUrl = `${API_URL.replace(/\/api\/?$/, "")}/docs/api`;

const productLinks = [
  { href: "/servicos", label: "Serviços" },
  { href: "/agendar", label: "Agendar" },
  { href: "/cadastro", label: "Criar conta" },
];

export function Footer() {
  return (
    <footer className="border-t border-border">
      <div className="mx-auto grid max-w-4xl gap-8 px-4 py-12 sm:grid-cols-[1.4fr_1fr_1fr]">
        <div className="flex max-w-56 flex-col gap-2">
          <div className="flex items-center gap-2 font-heading font-medium text-foreground">
            <Logo className="h-6 w-6" iconClassName="h-3.5 w-3.5" />
            Zelo
          </div>
          <p className="text-sm text-muted-foreground">
            Agendamento online pra negócios de serviço, sem conflito de horário.
          </p>
        </div>

        <div className="flex flex-col gap-2">
          <span className="text-sm font-medium text-foreground">Produto</span>
          {productLinks.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              className="text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
              {link.label}
            </Link>
          ))}
        </div>

        <div className="flex flex-col gap-2">
          <span className="text-sm font-medium text-foreground">Projeto</span>
          <a
            href={apiDocsUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="text-sm text-muted-foreground transition-colors hover:text-foreground"
          >
            Documentação da API
          </a>
          <a
            href="https://github.com/filipeyuan/agendamento-app"
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
          >
            <Code2 className="h-3.5 w-3.5" />
            Código no GitHub
          </a>
        </div>
      </div>

      <div className="border-t border-border">
        <div className="mx-auto flex max-w-4xl flex-col items-center justify-between gap-3 px-4 py-4 text-xs text-muted-foreground sm:flex-row">
          <span>© {new Date().getFullYear()} Zelo</span>
          <ApiStatus />
        </div>
      </div>
    </footer>
  );
}
