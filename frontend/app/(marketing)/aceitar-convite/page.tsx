"use client";

import { Suspense, useEffect, useState, type FormEvent } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { UserPlus } from "lucide-react";

import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Spinner } from "@/components/ui/spinner";
import { getInvitePreview } from "@/lib/api/invites";
import { useAuth } from "@/lib/auth/context";
import { formatApiError } from "@/lib/utils/format-error";

function AceitarConviteForm() {
  const searchParams = useSearchParams();
  const token = searchParams.get("token");
  const router = useRouter();
  const { acceptInvite } = useAuth();

  const [businessName, setBusinessName] = useState<string | null>(null);
  const [previewError, setPreviewError] = useState<string | null>(null);
  const [isLoadingPreview, setIsLoadingPreview] = useState(Boolean(token));

  const [name, setName] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    if (!token) return;

    getInvitePreview(token)
      .then((preview) => setBusinessName(preview.business_name))
      .catch((err) => setPreviewError(formatApiError(err)))
      .finally(() => setIsLoadingPreview(false));
  }, [token]);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    if (!token) return;

    setError(null);
    setIsSubmitting(true);

    try {
      const user = await acceptInvite({
        token,
        name,
        password,
        password_confirmation: passwordConfirmation,
      });
      router.push(user.role === "admin" ? "/admin/agendamentos" : "/servicos");
    } catch (err) {
      setError(formatApiError(err));
      setIsSubmitting(false);
    }
  }

  if (!token) {
    return <Alert variant="destructive">Link de convite inválido.</Alert>;
  }

  if (isLoadingPreview) {
    return (
      <div className="flex justify-center py-6">
        <Spinner className="h-6 w-6 text-muted-foreground" />
      </div>
    );
  }

  if (previewError) {
    return <Alert variant="destructive">{previewError}</Alert>;
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
      {businessName && (
        <p className="text-sm text-muted-foreground">
          Você foi convidado pra fazer parte da equipe de <strong className="text-foreground">{businessName}</strong>.
        </p>
      )}
      {error && <Alert variant="destructive">{error}</Alert>}

      <div>
        <Label htmlFor="name">Seu nome</Label>
        <Input id="name" required value={name} onChange={(event) => setName(event.target.value)} />
      </div>

      <div>
        <Label htmlFor="password">Senha</Label>
        <Input
          id="password"
          type="password"
          required
          minLength={8}
          value={password}
          onChange={(event) => setPassword(event.target.value)}
        />
      </div>

      <div>
        <Label htmlFor="password_confirmation">Confirmar senha</Label>
        <Input
          id="password_confirmation"
          type="password"
          required
          value={passwordConfirmation}
          onChange={(event) => setPasswordConfirmation(event.target.value)}
        />
      </div>

      <Button type="submit" disabled={isSubmitting} className="mt-2">
        <UserPlus className="h-4 w-4" />
        {isSubmitting ? "Entrando..." : "Aceitar convite"}
      </Button>
    </form>
  );
}

export default function AceitarConvitePage() {
  return (
    <main className="flex flex-1 items-center justify-center p-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle>Aceitar convite</CardTitle>
          <CardDescription>Crie sua senha pra acessar o painel administrativo.</CardDescription>
        </CardHeader>
        <CardContent>
          <Suspense>
            <AceitarConviteForm />
          </Suspense>
        </CardContent>
      </Card>
    </main>
  );
}
