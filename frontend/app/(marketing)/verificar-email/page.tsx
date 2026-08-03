"use client";

import { Suspense, useEffect, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { CheckCircle2, XCircle } from "lucide-react";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Spinner } from "@/components/ui/spinner";
import { verifyEmail } from "@/lib/api/auth";
import { formatApiError } from "@/lib/utils/format-error";

type Status = "loading" | "success" | "error";

function VerificarEmailContent() {
  const searchParams = useSearchParams();
  const token = searchParams.get("token");
  const [status, setStatus] = useState<Status>(token ? "loading" : "error");
  const [error, setError] = useState<string | null>(token ? null : "Link inválido.");

  useEffect(() => {
    if (!token) return;

    verifyEmail({ token })
      .then(() => setStatus("success"))
      .catch((err) => {
        setStatus("error");
        setError(formatApiError(err));
      });
  }, [token]);

  if (status === "loading") {
    return (
      <div className="flex flex-col items-center gap-3 py-4">
        <Spinner className="h-6 w-6 text-muted-foreground" />
        <p className="text-sm text-muted-foreground">Confirmando seu e-mail...</p>
      </div>
    );
  }

  if (status === "success") {
    return (
      <div className="flex flex-col items-center gap-3 py-4 text-center">
        <CheckCircle2 className="h-10 w-10 text-success" />
        <p className="text-sm text-foreground">Seu e-mail foi confirmado com sucesso.</p>
        <Link href="/login" className="text-sm font-medium text-primary hover:underline">
          Ir para o login
        </Link>
      </div>
    );
  }

  return (
    <div className="flex flex-col items-center gap-3 py-4 text-center">
      <XCircle className="h-10 w-10 text-destructive" />
      <p className="text-sm text-foreground">{error ?? "Não foi possível confirmar seu e-mail."}</p>
      <Link href="/login" className="text-sm font-medium text-primary hover:underline">
        Ir para o login
      </Link>
    </div>
  );
}

export default function VerificarEmailPage() {
  return (
    <main className="flex flex-1 items-center justify-center p-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle>Confirmar e-mail</CardTitle>
          <CardDescription>Só um instante.</CardDescription>
        </CardHeader>
        <CardContent>
          <Suspense>
            <VerificarEmailContent />
          </Suspense>
        </CardContent>
      </Card>
    </main>
  );
}
