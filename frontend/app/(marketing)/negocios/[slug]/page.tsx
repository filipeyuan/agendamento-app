import Link from "next/link";
import { notFound } from "next/navigation";
import { CalendarPlus, Clock, PackageSearch } from "lucide-react";

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

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-10">
      <p className="mb-1 text-sm text-muted-foreground">
        <Link href="/servicos" className="hover:underline">
          Negócios
        </Link>
      </p>
      <h1 className="mb-6 text-2xl font-semibold text-foreground">{business.name}</h1>

      {services.length === 0 && (
        <EmptyState
          icon={PackageSearch}
          title="Nenhum serviço disponível no momento"
          description="Volte mais tarde ou entre em contato com o estabelecimento."
        />
      )}

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {services.map((service, index) => (
          <Reveal key={service.id} delay={Math.min(index * 0.05, 0.3)}>
            <Card className="shadow-[var(--elevation-sm)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[var(--elevation-md)]">
              <CardHeader>
                <CardTitle>{service.name}</CardTitle>
                {service.description && (
                  <p className="text-sm text-muted-foreground">{service.description}</p>
                )}
              </CardHeader>
              <CardContent className="flex items-center justify-between text-sm">
                <span className="flex items-center gap-1.5 text-muted-foreground">
                  <Clock className="h-4 w-4" />
                  {service.duration_minutes} min
                </span>
                <span className="font-medium text-foreground">{formatPrice(service.price)}</span>
              </CardContent>
              <CardFooter>
                <Link
                  href={`/agendar?service=${service.id}&business=${slug}`}
                  className={cn(buttonVariants({ className: "w-full rounded-full" }))}
                >
                  <CalendarPlus className="h-4 w-4" />
                  Agendar
                </Link>
              </CardFooter>
            </Card>
          </Reveal>
        ))}
      </div>
    </main>
  );
}
