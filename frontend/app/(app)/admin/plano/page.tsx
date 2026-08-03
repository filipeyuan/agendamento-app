"use client";

import { Suspense, useEffect, useRef, useState } from "react";
import Image from "next/image";
import { useSearchParams } from "next/navigation";
import { Check, Crown, ImageIcon, Lock, RotateCcw, Sparkles } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { createBillingCheckout, createBillingPortalSession } from "@/lib/api/billing";
import {
  removeBusinessBanner,
  removeBusinessLogo,
  updateBusinessAccentColor,
  updateBusinessBanner,
  updateBusinessLogo,
} from "@/lib/api/businesses";
import { useAuth } from "@/lib/auth/context";
import { cn } from "@/lib/utils/cn";
import { formatApiError } from "@/lib/utils/format-error";

const DEFAULT_ACCENT_COLOR = "#2563eb";

function isColorTooLight(hex: string) {
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  const brightness = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
  return brightness > 0.75;
}

const proFeatures = [
  "Serviços cadastrados ilimitados",
  "Assistente por IA liberado pros seus clientes",
  "Sem marca \"Powered by Zelo\" na sua página pública",
];

function LogoSection() {
  const { user, refreshUser } = useAuth();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);

  const logoUrl = user?.business?.logo_url ?? null;

  async function handleFileSelected(file: File) {
    setError(null);
    setIsSaving(true);

    try {
      await updateBusinessLogo(file);
      await refreshUser();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSaving(false);
      if (fileInputRef.current) fileInputRef.current.value = "";
    }
  }

  async function handleRemove() {
    setError(null);
    setIsSaving(true);

    try {
      await removeBusinessLogo();
      await refreshUser();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <div className="flex flex-col gap-3">
      <p className="text-sm font-medium text-foreground">Logo</p>
      {error && <Alert variant="destructive">{error}</Alert>}

      <div className="flex items-center gap-4">
        <div className="relative flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border bg-muted">
          {logoUrl ? (
            <Image src={logoUrl} alt="Logo do negócio" fill className="object-cover" />
          ) : (
            <ImageIcon className="h-6 w-6 text-muted-foreground" />
          )}
        </div>
        <div className="flex flex-col gap-1">
          <p className="text-sm text-muted-foreground">
            Aparece na sua página pública de agendamento. PNG, JPG ou WEBP, até 2MB.
          </p>
          <div className="flex gap-2">
            <Button
              type="button"
              size="sm"
              variant="outline"
              onClick={() => fileInputRef.current?.click()}
              disabled={isSaving}
            >
              {isSaving ? "Enviando..." : logoUrl ? "Trocar logo" : "Enviar logo"}
            </Button>
            {logoUrl && (
              <Button type="button" size="sm" variant="ghost" onClick={handleRemove} disabled={isSaving}>
                Remover
              </Button>
            )}
          </div>
        </div>
      </div>

      <input
        ref={fileInputRef}
        type="file"
        accept="image/png,image/jpeg,image/webp"
        className="hidden"
        onChange={(e) => {
          const file = e.target.files?.[0];
          if (file) void handleFileSelected(file);
        }}
      />
    </div>
  );
}

function BannerSection() {
  const { user, refreshUser } = useAuth();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);

  const bannerUrl = user?.business?.banner_url ?? null;

  async function handleFileSelected(file: File) {
    setError(null);
    setIsSaving(true);

    try {
      await updateBusinessBanner(file);
      await refreshUser();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSaving(false);
      if (fileInputRef.current) fileInputRef.current.value = "";
    }
  }

  async function handleRemove() {
    setError(null);
    setIsSaving(true);

    try {
      await removeBusinessBanner();
      await refreshUser();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <div className="flex flex-col gap-3">
      <p className="text-sm font-medium text-foreground">Banner de capa</p>
      {error && <Alert variant="destructive">{error}</Alert>}

      <div className="relative flex h-28 w-full items-center justify-center overflow-hidden rounded-xl border border-border bg-muted sm:h-32">
        {bannerUrl ? (
          <Image src={bannerUrl} alt="Banner do negócio" fill className="object-cover" />
        ) : (
          <ImageIcon className="h-6 w-6 text-muted-foreground" />
        )}
      </div>

      <div className="flex items-center justify-between gap-2">
        <p className="text-sm text-muted-foreground">
          Aparece no topo da sua página pública. PNG, JPG ou WEBP, até 4MB.
        </p>
        <div className="flex shrink-0 gap-2">
          <Button
            type="button"
            size="sm"
            variant="outline"
            onClick={() => fileInputRef.current?.click()}
            disabled={isSaving}
          >
            {isSaving ? "Enviando..." : bannerUrl ? "Trocar" : "Enviar"}
          </Button>
          {bannerUrl && (
            <Button type="button" size="sm" variant="ghost" onClick={handleRemove} disabled={isSaving}>
              Remover
            </Button>
          )}
        </div>
      </div>

      <input
        ref={fileInputRef}
        type="file"
        accept="image/png,image/jpeg,image/webp"
        className="hidden"
        onChange={(e) => {
          const file = e.target.files?.[0];
          if (file) void handleFileSelected(file);
        }}
      />
    </div>
  );
}

function AccentColorSection() {
  const { user, refreshUser } = useAuth();
  const [color, setColor] = useState(user?.business?.accent_color ?? DEFAULT_ACCENT_COLOR);
  const [error, setError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);

  const savedColor = user?.business?.accent_color ?? null;
  const hasChanges = color !== (savedColor ?? DEFAULT_ACCENT_COLOR);

  async function handleSave() {
    setError(null);
    setIsSaving(true);

    try {
      await updateBusinessAccentColor(color);
      await refreshUser();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSaving(false);
    }
  }

  async function handleReset() {
    setColor(DEFAULT_ACCENT_COLOR);
    setError(null);
    setIsSaving(true);

    try {
      await updateBusinessAccentColor(null);
      await refreshUser();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <div className="flex flex-col gap-3">
      <p className="text-sm font-medium text-foreground">Cor de destaque</p>
      {error && <Alert variant="destructive">{error}</Alert>}

      <div className="flex items-center gap-4">
        <label className="relative flex h-10 w-10 shrink-0 cursor-pointer overflow-hidden rounded-full border border-border">
          <input
            type="color"
            value={color}
            onChange={(e) => setColor(e.target.value)}
            className="absolute -inset-2 cursor-pointer"
            aria-label="Escolher cor de destaque"
          />
        </label>
        <div className="flex flex-1 flex-col gap-1">
          <p className="text-sm text-muted-foreground">Usada nos botões e destaques da sua página pública.</p>
          {isColorTooLight(color) && (
            <p className="text-xs text-warning">Cor muito clara pode dificultar a leitura do texto nos botões.</p>
          )}
          <div className="flex gap-2">
            <Button type="button" size="sm" onClick={handleSave} disabled={isSaving || !hasChanges}>
              {isSaving ? "Salvando..." : "Salvar cor"}
            </Button>
            {savedColor && (
              <Button type="button" size="sm" variant="ghost" onClick={handleReset} disabled={isSaving}>
                <RotateCcw className="h-3.5 w-3.5" />
                Restaurar padrão
              </Button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

function PersonalizacaoCard() {
  const { user } = useAuth();
  const isPro = user?.business?.plan === "pro";

  return (
    <Card className="relative overflow-hidden">
      <CardHeader>
        <CardTitle className="text-base">Personalização</CardTitle>
      </CardHeader>
      <CardContent className={cn("flex flex-col gap-6", !isPro && "pointer-events-none select-none blur-sm")}>
        <LogoSection />
        <div className="border-t border-border" />
        <BannerSection />
        <div className="border-t border-border" />
        <AccentColorSection />
      </CardContent>
      {!isPro && (
        <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 rounded-b-2xl bg-card/70 backdrop-blur-[1px]">
          <span className="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary">
            <Lock className="h-4 w-4" />
          </span>
          <p className="text-sm font-medium text-foreground">Recurso do plano Pro</p>
        </div>
      )}
    </Card>
  );
}

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

      <PersonalizacaoCard />
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
