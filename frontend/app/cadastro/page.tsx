"use client";

import { useState, type FormEvent } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { UserPlus } from "lucide-react";

import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useAuth } from "@/lib/auth/context";
import { formatApiError } from "@/lib/utils/format-error";
import { cn } from "@/lib/utils/cn";

type AccountType = "client" | "business";

const ACCOUNT_TYPE_LABEL: Record<AccountType, string> = {
  client: "Sou cliente",
  business: "Tenho um negócio",
};

export default function CadastroPage() {
  const { register } = useAuth();
  const router = useRouter();

  const [accountType, setAccountType] = useState<AccountType>("client");
  const [name, setName] = useState("");
  const [businessName, setBusinessName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      const user = await register({
        name,
        email,
        phone: phone || undefined,
        password,
        password_confirmation: passwordConfirmation,
        account_type: accountType,
        business_name: accountType === "business" ? businessName : undefined,
      });
      router.push(user.role === "admin" ? "/admin/dashboard" : "/servicos");
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <main className="flex flex-1 items-center justify-center p-4">
      <Card className="w-full max-w-sm">
        <CardHeader>
          <CardTitle>Criar conta</CardTitle>
          <CardDescription>
            {accountType === "client"
              ? "Cadastre-se para agendar um serviço."
              : "Cadastre seu negócio e comece a receber agendamentos."}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            {error && <Alert variant="destructive">{error}</Alert>}

            <div className="flex gap-1 rounded-md border border-border bg-muted/40 p-1">
              {(Object.keys(ACCOUNT_TYPE_LABEL) as AccountType[]).map((option) => (
                <button
                  key={option}
                  type="button"
                  onClick={() => setAccountType(option)}
                  className={cn(
                    "flex-1 rounded px-3 py-1.5 text-sm font-medium transition-colors",
                    accountType === option
                      ? "bg-card text-foreground shadow-sm"
                      : "text-muted-foreground hover:text-foreground"
                  )}
                >
                  {ACCOUNT_TYPE_LABEL[option]}
                </button>
              ))}
            </div>

            {accountType === "business" && (
              <div>
                <Label htmlFor="business_name">Nome do negócio</Label>
                <Input
                  id="business_name"
                  required
                  value={businessName}
                  onChange={(e) => setBusinessName(e.target.value)}
                />
              </div>
            )}

            <div>
              <Label htmlFor="name">{accountType === "business" ? "Seu nome" : "Nome"}</Label>
              <Input id="name" required value={name} onChange={(e) => setName(e.target.value)} />
            </div>

            <div>
              <Label htmlFor="email">E-mail</Label>
              <Input
                id="email"
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>

            <div>
              <Label htmlFor="phone">Telefone (opcional)</Label>
              <Input id="phone" value={phone} onChange={(e) => setPhone(e.target.value)} />
            </div>

            <div>
              <Label htmlFor="password">Senha</Label>
              <Input
                id="password"
                type="password"
                required
                minLength={8}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>

            <div>
              <Label htmlFor="password_confirmation">Confirmar senha</Label>
              <Input
                id="password_confirmation"
                type="password"
                required
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
              />
            </div>

            <Button type="submit" disabled={isSubmitting} className="mt-2">
              <UserPlus className="h-4 w-4" />
              {isSubmitting ? "Criando conta..." : "Criar conta"}
            </Button>

            <p className="text-center text-sm text-muted-foreground">
              Já tem conta?{" "}
              <Link href="/login" className="font-medium text-primary hover:underline">
                Entrar
              </Link>
            </p>
          </form>
        </CardContent>
      </Card>
    </main>
  );
}
