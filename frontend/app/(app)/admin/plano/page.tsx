"use client";

import { Suspense, useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { Check, Crown, Sparkles } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { createBillingCheckout, createBillingPortalSession } from "@/lib/api/billing";
import { useAuth } from "@/lib/auth/context";
import { formatApiError } from "@/lib/utils/format-error";

const proFeatures = [
  "Serviços cadastrados ilimitados",
  "Assistente por IA liberado pros seus clientes",
  "Sem marca \"Powered by Zelo\" na sua página pública",
];

function PlanoPanel() {
  const { user, refreshUser } = useAuth();
  const searchParams = useSearchParams();
  const [error, setError] = useState<string | null>(null);
  const [isRedirecting, setIsRedirecting] = useState(false);

  const upgradeStatus = searchParams.get("upgrade");
  const isPro = user?.business?.plan === "pro";

  useEffect(() => {
    if (upgradeStatus === "success") {
      refreshUser();
      const timeout = setTimeout(refreshUser, 3000);
      return () => clearTimeout(timeout);
    }
  }, [upgradeStatus, refreshUser]);

  async function handleUpgrade() {
    setError(null);
    setIsRedirecting(true);

    try {
      const { checkoutUrl } = await createBillingCheckout();
      window.location.href = checkoutUrl;
    } catch (err) {
      setError(formatApiError(err));
      setIsRedirecting(false);
    }
  }

  async function handleManage() {
    setError(null);
    setIsRedirecting(true);

    try {
      const { portalUrl } = await createBillingPortalSession();
      window.location.href = portalUrl;
    } catch (err) {
      setError(formatApiError(err));
      setIsRedirecting(false);
    }
  }

  return (
    <div className="flex flex-col gap-4">
      {error && <Alert variant="destructive">{error}</Alert>}

      {upgradeStatus === "success" && (
        <Alert variant="success">
          Pagamento confirmado. Seu plano deve atualizar pra Pro em instantes.
        </Alert>
      )}

      {upgradeStatus === "cancelled" && (
        <Alert>Nenhum problema, a assinatura não foi concluída. Pode tentar de novo quando quiser.</Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            {isPro ? <Crown className="h-4 w-4 text-primary" /> : <Sparkles className="h-4 w-4 text-primary" />}
            Seu plano atual
            <Badge variant={isPro ? "default" : "outline"} className="ml-1">
              {isPro ? "Pro" : "Free"}
            </Badge>
          </CardTitle>
        </CardHeader>
        <CardContent className="flex flex-col gap-4">
          {isPro ? (
            <>
              <p className="text-sm text-muted-foreground">
                Sua assinatura Pro está ativa. Gerencie forma de pagamento, veja faturas ou cancele quando
                quiser.
              </p>
              <Button className="w-fit" variant="outline" onClick={handleManage} disabled={isRedirecting}>
                {isRedirecting ? "Abrindo..." : "Gerenciar assinatura"}
              </Button>
            </>
          ) : (
            <>
              <div>
                <p className="text-2xl font-bold text-foreground">
                  R$ 29,90<span className="text-sm font-normal text-muted-foreground">/mês</span>
                </p>
                <p className="text-sm text-muted-foreground">Cancele quando quiser, sem fidelidade.</p>
              </div>
              <ul className="flex flex-col gap-2">
                {proFeatures.map((feature) => (
                  <li key={feature} className="flex items-start gap-2 text-sm text-foreground">
                    <Check className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                    {feature}
                  </li>
                ))}
              </ul>
              <Button className="w-fit" onClick={handleUpgrade} disabled={isRedirecting}>
                {isRedirecting ? "Abrindo..." : "Assinar Pro"}
              </Button>
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

export default function PlanoPage() {
  return (
    <RequireAuth role="admin">
      <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-10">
        <h1 className="mb-6 text-2xl font-semibold text-foreground">Plano</h1>
        <Suspense>
          <PlanoPanel />
        </Suspense>
      </main>
    </RequireAuth>
  );
}
