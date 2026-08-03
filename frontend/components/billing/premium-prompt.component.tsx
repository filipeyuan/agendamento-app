"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import { Check, Crown } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Dialog } from "@/components/ui/dialog";
import { dismissPremiumPrompt } from "@/lib/api/billing";
import { useAuth } from "@/lib/auth/context";

const proFeatures = [
  "Serviços cadastrados ilimitados",
  "Assistente por IA sem limite de mensagens",
  "Sem marca \"Powered by Zelo\" na sua página",
];

export function PremiumPrompt() {
  const { user, refreshUser } = useAuth();
  const router = useRouter();
  const [dismissedLocally, setDismissedLocally] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const shouldPrompt = user?.role === "admin" && user.business?.premium_prompt_due === true;

  async function dismiss() {
    setDismissedLocally(true);
    setIsSubmitting(true);

    try {
      await dismissPremiumPrompt();
      await refreshUser();
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleSeePlans() {
    await dismiss();
    router.push("/admin/plano");
  }

  if (!shouldPrompt) {
    return null;
  }

  return (
    <Dialog open={!dismissedLocally} onClose={dismiss} title="Conheça o plano Pro" size="lg">
      <div className="flex flex-col gap-5">
        <p className="text-2xl font-bold text-foreground">
          R$ 29,90<span className="text-sm font-normal text-muted-foreground">/mês</span>
        </p>

        <ul className="flex flex-col gap-2.5">
          {proFeatures.map((feature) => (
            <li key={feature} className="flex items-start gap-2 text-sm text-foreground">
              <Check className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
              {feature}
            </li>
          ))}
        </ul>

        <div className="flex justify-end gap-2">
          <Button type="button" variant="ghost" onClick={dismiss} disabled={isSubmitting}>
            Agora não
          </Button>
          <Button type="button" onClick={handleSeePlans} disabled={isSubmitting}>
            <Crown className="h-4 w-4" />
            Ver plano Pro
          </Button>
        </div>
      </div>
    </Dialog>
  );
}
