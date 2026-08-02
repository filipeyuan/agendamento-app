"use client";

import { useState, type FormEvent } from "react";
import { useRouter } from "next/navigation";
import { KeyRound, PowerOff, Trash2, User as UserIcon } from "lucide-react";

import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Dialog } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useAuth } from "@/lib/auth/context";
import { formatApiError } from "@/lib/utils/format-error";

function ProfileForm() {
  const { user, updateProfile } = useAuth();
  const [name, setName] = useState(user?.name ?? "");
  const [email, setEmail] = useState(user?.email ?? "");
  const [phone, setPhone] = useState(user?.phone ?? "");
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSuccess(false);
    setIsSubmitting(true);

    try {
      await updateProfile({ name, email, phone: phone || undefined });
      setSuccess(true);
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-base">
          <UserIcon className="h-4 w-4 text-primary" />
          Dados pessoais
        </CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          {error && <Alert variant="destructive">{error}</Alert>}
          {success && <Alert variant="success">Dados atualizados com sucesso.</Alert>}

          <div>
            <Label htmlFor="name">Nome</Label>
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

          <Button type="submit" disabled={isSubmitting} className="w-fit">
            {isSubmitting ? "Salvando..." : "Salvar alterações"}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}

function PasswordForm() {
  const { updatePassword } = useAuth();
  const [currentPassword, setCurrentPassword] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSuccess(false);
    setIsSubmitting(true);

    try {
      await updatePassword({
        current_password: currentPassword,
        password,
        password_confirmation: passwordConfirmation,
      });
      setSuccess(true);
      setCurrentPassword("");
      setPassword("");
      setPasswordConfirmation("");
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-base">
          <KeyRound className="h-4 w-4 text-primary" />
          Alterar senha
        </CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          {error && <Alert variant="destructive">{error}</Alert>}
          {success && <Alert variant="success">Senha atualizada com sucesso.</Alert>}

          <div>
            <Label htmlFor="current_password">Senha atual</Label>
            <Input
              id="current_password"
              type="password"
              required
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
            />
          </div>

          <div>
            <Label htmlFor="password">Nova senha</Label>
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
            <Label htmlFor="password_confirmation">Confirmar nova senha</Label>
            <Input
              id="password_confirmation"
              type="password"
              required
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
            />
          </div>

          <Button type="submit" disabled={isSubmitting} className="w-fit">
            {isSubmitting ? "Salvando..." : "Atualizar senha"}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}

function DeactivateAccountSection() {
  const { deactivateAccount } = useAuth();
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  function close() {
    setOpen(false);
    setPassword("");
    setError(null);
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      await deactivateAccount(password);
      router.push("/");
    } catch (err) {
      setError(formatApiError(err));
      setIsSubmitting(false);
    }
  }

  return (
    <div className="flex items-start justify-between gap-4 border-b border-border pb-4 last:border-0 last:pb-0">
      <div>
        <p className="font-medium text-foreground">Desativar conta</p>
        <p className="text-sm text-muted-foreground">
          Sua conta fica inacessível até você entrar novamente. É possível reativar fazendo login.
        </p>
      </div>
      <Button type="button" variant="outline" className="w-fit shrink-0" onClick={() => setOpen(true)}>
        <PowerOff className="h-4 w-4" />
        Desativar
      </Button>

      <Dialog
        open={open}
        onClose={close}
        title="Desativar sua conta?"
        description="Você será desconectado agora. Pra voltar, basta fazer login de novo com a mesma senha."
      >
        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          {error && <Alert variant="destructive">{error}</Alert>}

          <div>
            <Label htmlFor="deactivate_password">Confirme sua senha</Label>
            <Input
              id="deactivate_password"
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </div>

          <div className="flex justify-end gap-2">
            <Button type="button" variant="ghost" onClick={close}>
              Cancelar
            </Button>
            <Button type="submit" variant="destructive" disabled={isSubmitting}>
              {isSubmitting ? "Desativando..." : "Desativar conta"}
            </Button>
          </div>
        </form>
      </Dialog>
    </div>
  );
}

function DeleteAccountSection() {
  const { deleteAccount } = useAuth();
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const [password, setPassword] = useState("");
  const [confirmationText, setConfirmationText] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  function close() {
    setOpen(false);
    setPassword("");
    setConfirmationText("");
    setError(null);
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      await deleteAccount(password);
      router.push("/");
    } catch (err) {
      setError(formatApiError(err));
      setIsSubmitting(false);
    }
  }

  return (
    <div className="flex items-start justify-between gap-4">
      <div>
        <p className="font-medium text-foreground">Excluir conta permanentemente</p>
        <p className="text-sm text-muted-foreground">
          Apaga sua conta e seus dados de vez. Essa ação não pode ser desfeita.
        </p>
      </div>
      <Button type="button" variant="destructive" className="w-fit shrink-0" onClick={() => setOpen(true)}>
        <Trash2 className="h-4 w-4" />
        Excluir
      </Button>

      <Dialog
        open={open}
        onClose={close}
        title="Excluir sua conta de vez?"
        description="Todos os seus dados são apagados permanentemente. Não dá pra desfazer essa ação."
      >
        <form onSubmit={handleSubmit} className="flex flex-col gap-4">
          {error && <Alert variant="destructive">{error}</Alert>}

          <div>
            <Label htmlFor="delete_password">Confirme sua senha</Label>
            <Input
              id="delete_password"
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </div>

          <div>
            <Label htmlFor="delete_confirmation">
              Digite <span className="font-semibold text-foreground">EXCLUIR</span> pra confirmar
            </Label>
            <Input
              id="delete_confirmation"
              required
              value={confirmationText}
              onChange={(e) => setConfirmationText(e.target.value)}
            />
          </div>

          <div className="flex justify-end gap-2">
            <Button type="button" variant="ghost" onClick={close}>
              Cancelar
            </Button>
            <Button
              type="submit"
              variant="destructive"
              disabled={isSubmitting || confirmationText !== "EXCLUIR"}
            >
              {isSubmitting ? "Excluindo..." : "Excluir conta"}
            </Button>
          </div>
        </form>
      </Dialog>
    </div>
  );
}

function DangerZone() {
  return (
    <Card className="border-destructive/30">
      <CardHeader>
        <CardTitle className="text-base text-destructive">Zona de risco</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-col gap-4">
        <DeactivateAccountSection />
        <DeleteAccountSection />
      </CardContent>
    </Card>
  );
}

export default function PerfilPage() {
  return (
    <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-10">
      <h1 className="mb-6 text-3xl font-bold tracking-tight text-foreground">Meu perfil</h1>
      <div className="flex flex-col gap-6">
        <ProfileForm />
        <PasswordForm />
        <DangerZone />
      </div>
    </main>
  );
}
