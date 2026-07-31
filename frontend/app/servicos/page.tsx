import Link from "next/link";
import { ArrowRight, Store as StoreIcon } from "lucide-react";

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
        {businesses.map((business) => (
          <Card key={business.id}>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <StoreIcon className="h-4 w-4 text-muted-foreground" />
                {business.name}
              </CardTitle>
            </CardHeader>
            <CardFooter>
              <Link
                href={`/negocios/${business.slug}`}
                className={cn(buttonVariants({ className: "w-full" }))}
              >
                Ver serviços
                <ArrowRight className="h-4 w-4" />
              </Link>
            </CardFooter>
          </Card>
        ))}
      </div>
    </main>
  );
}
