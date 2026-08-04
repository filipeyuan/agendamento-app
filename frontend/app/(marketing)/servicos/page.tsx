import Link from "next/link";
import { ArrowRight, Store as StoreIcon } from "lucide-react";

import { Reveal } from "@/components/motion/reveal.component";
import { Card, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { buttonVariants } from "@/components/ui/button";
import { listBusinesses } from "@/lib/api/businesses";
import { cn } from "@/lib/utils/cn";

export default async function ServicosPage() {
  const businesses = await listBusinesses();

  return (
    <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-10">
      <h1 className="mb-6 text-2xl font-semibold text-foreground">Escolha um negócio</h1>

      {businesses.length === 0 && (
        <EmptyState
          icon={StoreIcon}
          title="Nenhum negócio cadastrado no momento"
          description="Volte mais tarde ou cadastre o seu."
        />
      )}

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {businesses.map((business, index) => (
          <Reveal key={business.id} delay={Math.min(index * 0.05, 0.3)} className="h-full">
            <Link href={`/negocios/${business.slug}`} className="group flex h-full">
              <Card className="flex h-full flex-col shadow-[var(--elevation-sm)] transition-all duration-200 group-hover:-translate-y-0.5 group-hover:shadow-[var(--elevation-md)]">
                <CardHeader className="flex-1">
                  <CardTitle className="flex items-center gap-3">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                      <StoreIcon className="h-4 w-4" />
                    </span>
                    {business.name}
                  </CardTitle>
                </CardHeader>
                <CardFooter>
                  <span className={cn(buttonVariants({ className: "w-full rounded-full" }))}>
                    Ver serviços
                    <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                  </span>
                </CardFooter>
              </Card>
            </Link>
          </Reveal>
        ))}
      </div>
    </main>
  );
}
