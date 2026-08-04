import type { CSSProperties } from "react";
import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import { CalendarPlus, Clock, PackageSearch, Sparkles } from "lucide-react";

import { Reveal } from "@/components/motion/reveal.component";
import { buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { getBusiness } from "@/lib/api/businesses";
import { listServices } from "@/lib/api/services";
import { ApiError } from "@/lib/api/client";
import { cn } from "@/lib/utils/cn";

function formatPrice(price: number) {
  return price.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

export default async function NegocioServicosPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;

  let business;
  try {
    business = await getBusiness(slug);
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) notFound();
    throw err;
  }

  const services = await listServices(slug);
  const accentStyle = business.accent_color
    ? ({ "--primary": business.accent_color } as CSSProperties)
    : undefined;

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-10" style={accentStyle}>
      <p className="mb-1 text-sm text-muted-foreground">
        <Link href="/servicos" className="hover:underline">
          Negócios
        </Link>
      </p>

      {business.banner_url && (
        <div className="relative mt-3 h-40 w-full overflow-hidden rounded-2xl border border-border bg-muted sm:h-56">
          <Image src={business.banner_url} alt="" fill priority className="object-cover" />
        </div>
      )}

      <div
        className={cn(
          "mb-6 flex items-center gap-3",
          business.banner_url ? "-mt-8 ml-4 items-end sm:-mt-10" : "mt-3"
        )}
      >
        {business.logo_url && (
          <div
            className={cn(
              "relative h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-border bg-muted",
              business.banner_url && "h-16 w-16 border-4 border-background shadow-md sm:h-20 sm:w-20"
            )}
          >
            <Image src={business.logo_url} alt={business.name} fill className="object-cover" />
          </div>
        )}
        <h1 className={cn("text-2xl font-semibold text-foreground", business.banner_url && "pb-1")}>
          {business.name}
        </h1>
      </div>

      {services.length === 0 && (
        <EmptyState
          icon={PackageSearch}
          title="Nenhum serviço disponível no momento"
          description="Volte mais tarde ou entre em contato com o estabelecimento."
        />
      )}

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {services.map((service, index) => (
          <Reveal key={service.id} delay={Math.min(index * 0.05, 0.3)} className="h-full">
            <Link href={`/agendar?service=${service.id}&business=${slug}`} className="group flex h-full">
              <Card className="flex h-full flex-col shadow-[var(--elevation-sm)] transition-all duration-200 group-hover:-translate-y-0.5 group-hover:shadow-[var(--elevation-md)]">
                <CardHeader>
                  <CardTitle>{service.name}</CardTitle>
                  {service.description && (
                    <p className="text-sm text-muted-foreground">{service.description}</p>
                  )}
                </CardHeader>
                <CardContent className="flex flex-1 items-center justify-between text-sm">
                  <span className="flex items-center gap-1.5 text-muted-foreground">
                    <Clock className="h-4 w-4" />
                    {service.duration_minutes} min
                  </span>
                  <span className="font-medium text-foreground">{formatPrice(service.price)}</span>
                </CardContent>
                <CardFooter>
                  <span className={cn(buttonVariants({ className: "w-full rounded-full" }))}>
                    <CalendarPlus className="h-4 w-4" />
                    Agendar
                  </span>
                </CardFooter>
              </Card>
            </Link>
          </Reveal>
        ))}
      </div>

      {business.plan === "free" && (
        <Link
          href="/"
          className="mt-10 flex w-fit items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground"
        >
          <Sparkles className="h-3.5 w-3.5" />
          Powered by Zelo
        </Link>
      )}
    </main>
  );
}
