import Link from "next/link";
import { CalendarPlus, Clock, PackageSearch } from "lucide-react";

import { buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { listServices } from "@/lib/api/services";
import { cn } from "@/lib/utils/cn";

function formatPrice(price: number) {
  return price.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

export default async function ServicosPage() {
  const services = await listServices();

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-10">
      <h1 className="mb-6 text-2xl font-semibold text-foreground">Serviços disponíveis</h1>

      {services.length === 0 && (
        <EmptyState
          icon={PackageSearch}
          title="Nenhum serviço disponível no momento"
          description="Volte mais tarde ou entre em contato com o estabelecimento."
        />
      )}

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {services.map((service) => (
          <Card key={service.id}>
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
                href={`/agendar?service=${service.id}`}
                className={cn(buttonVariants({ className: "w-full" }))}
              >
                <CalendarPlus className="h-4 w-4" />
                Agendar
              </Link>
            </CardFooter>
          </Card>
        ))}
      </div>
    </main>
  );
}
