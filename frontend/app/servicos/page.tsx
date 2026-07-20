import Link from "next/link";

import { buttonVariants } from "@/components/ui/button";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { listServices } from "@/lib/api/services";

function formatPrice(price: number) {
  return price.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

export default async function ServicosPage() {
  const services = await listServices();

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-10">
      <h1 className="mb-6 text-2xl font-semibold text-foreground">Serviços disponíveis</h1>

      {services.length === 0 && (
        <p className="text-muted-foreground">Nenhum serviço disponível no momento.</p>
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
              <span className="text-muted-foreground">{service.duration_minutes} min</span>
              <span className="font-medium text-foreground">{formatPrice(service.price)}</span>
            </CardContent>
            <CardFooter>
              <Link
                href={`/agendar?service=${service.id}`}
                className={buttonVariants({ className: "w-full" })}
              >
                Agendar
              </Link>
            </CardFooter>
          </Card>
        ))}
      </div>
    </main>
  );
}
