"use client";

import { Suspense, useState, type FormEvent } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { KeyRound } from "lucide-react";

import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { resetPassword } from "@/lib/api/auth";
import { formatApiError } from "@/lib/utils/format-error";

function RedefinirSenhaForm() {
  const searchParams = useSearchParams();
  const token = searchParams.get("token");
  const email = searchParams.get("email");

  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    if (!token || !email) return;

    setError(null);
    setIsSubmitting(true);

    try {
      await resetPassword({
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      });
      setSuccess(true);
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (!token || !email) {
    return (
      <Alert variant="destructive">
        Link inválido. Peça um novo link em{" "}
        <Link href="/esqueci-senha" className="underline">
          esqueci minha senha
        </Link>
        .
      </Alert>
    );
  }

  if (success) {
    return (
      <div className="flex flex-col gap-4">
        <Alert variant="success">Senha redefinida com sucesso.</Alert>
        <Link href="/login" className="text-center text-sm font-medium text-primary hover:underline">
          Ir para o login
        </Link>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
      {error && <Alert variant="destructive">{error}</Alert>}

      <div>
        <Label htmlFor="password">Nova senha</Label>
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
        <Label htmlFor="password_confirmation">Confirmar nova senha</Label>
        <Input
          id="password_confirmation"
          type="password"
          required
          value={passwordConfirmation}
          onChange={(event) => setPasswordConfirmation(event.target.value)}
        />
      </div>

      <Button type="submit" disabled={isSubmitting} className="mt-2">
        <KeyRound className="h-4 w-4" />
        {isSubmitting ? "Redefinindo..." : "Redefinir senha"}
      </Button>
    </form>
  );
}

export default function RedefinirSenhaPage() {
  return (
    <main className="flex flex-1 items-center justify-center p-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle>Redefinir senha</CardTitle>
          <CardDescription>Escolha uma nova senha pra sua conta.</CardDescription>
        </CardHeader>
        <CardContent>
          <Suspense>
            <RedefinirSenhaForm />
          </Suspense>
        </CardContent>
      </Card>
    </main>
  );
}
