"use client";

import { useState } from "react";
import useSWR from "swr";
import { Mail, Trash2, UserPlus } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Dialog } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Spinner } from "@/components/ui/spinner";
import { cancelInvite, inviteTeamMember, listTeam, removeMember } from "@/lib/api/team";
import { useAuth } from "@/lib/auth/context";
import { formatApiError } from "@/lib/utils/format-error";

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString("pt-BR");
}

function InviteForm({ onInvited }: { onInvited: () => void }) {
  const [email, setEmail] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setSuccess(null);
    setIsSubmitting(true);

    try {
      await inviteTeamMember(email);
      setSuccess(`Convite enviado pra ${email}.`);
      setEmail("");
      onInvited();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-3">
      {error && <Alert variant="destructive">{error}</Alert>}
      {success && <Alert variant="success">{success}</Alert>}

      <div>
        <Label htmlFor="invite-email">Convidar por e-mail</Label>
        <div className="flex gap-2">
          <Input
            id="invite-email"
            type="email"
            required
            placeholder="pessoa@exemplo.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
          />
          <Button type="submit" disabled={isSubmitting} className="shrink-0">
            <UserPlus className="h-4 w-4" />
            {isSubmitting ? "Enviando..." : "Convidar"}
          </Button>
        </div>
      </div>
    </form>
  );
}

function EquipePanel() {
  const { user } = useAuth();
  const { data, error, isLoading, mutate } = useSWR("team", listTeam);
  const [pageError, setPageError] = useState<string | null>(null);
  const [memberToRemove, setMemberToRemove] = useState<{ id: number; name: string } | null>(null);
  const [isRemoving, setIsRemoving] = useState(false);

  async function handleCancelInvite(inviteId: number) {
    setPageError(null);
    try {
      await cancelInvite(inviteId);
      await mutate();
    } catch (err) {
      setPageError(formatApiError(err));
    }
  }

  async function handleRemoveMember() {
    if (!memberToRemove) return;
    setIsRemoving(true);
    setPageError(null);

    try {
      await removeMember(memberToRemove.id);
      setMemberToRemove(null);
      await mutate();
    } catch (err) {
      setPageError(formatApiError(err));
    } finally {
      setIsRemoving(false);
    }
  }

  return (
    <div className="flex flex-col gap-4">
      {pageError && <Alert variant="destructive">{pageError}</Alert>}

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Convidar pra equipe</CardTitle>
        </CardHeader>
        <CardContent>
          <InviteForm onInvited={() => mutate()} />
        </CardContent>
      </Card>

      {isLoading && (
        <div className="flex justify-center py-10">
          <Spinner className="h-6 w-6 text-muted-foreground" />
        </div>
      )}

      {error && !isLoading && <Alert variant="destructive">{formatApiError(error)}</Alert>}

      {data && (
        <>
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Membros</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-1">
              {data.members.map((member) => (
                <div
                  key={member.id}
                  className="flex items-center justify-between gap-3 rounded-lg px-2 py-2.5 hover:bg-accent"
                >
                  <div className="flex min-w-0 flex-col">
                    <span className="flex items-center gap-2 truncate text-sm font-medium text-foreground">
                      {member.name}
                      {member.id === user?.id && (
                        <Badge variant="outline" className="text-xs">
                          Você
                        </Badge>
                      )}
                    </span>
                    <span className="truncate text-xs text-muted-foreground">{member.email}</span>
                  </div>
                  {member.id !== user?.id && (
                    <Button
                      type="button"
                      size="sm"
                      variant="ghost"
                      onClick={() => setMemberToRemove({ id: member.id, name: member.name })}
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                      Remover
                    </Button>
                  )}
                </div>
              ))}
            </CardContent>
          </Card>

          {data.invites.length > 0 && (
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Convites pendentes</CardTitle>
              </CardHeader>
              <CardContent className="flex flex-col gap-1">
                {data.invites.map((invite) => (
                  <div
                    key={invite.id}
                    className="flex items-center justify-between gap-3 rounded-lg px-2 py-2.5 hover:bg-accent"
                  >
                    <div className="flex min-w-0 items-center gap-2">
                      <Mail className="h-4 w-4 shrink-0 text-muted-foreground" />
                      <div className="flex min-w-0 flex-col">
                        <span className="truncate text-sm font-medium text-foreground">{invite.email}</span>
                        <span className="truncate text-xs text-muted-foreground">
                          Expira em {formatDate(invite.expires_at)}
                        </span>
                      </div>
                    </div>
                    <Button
                      type="button"
                      size="sm"
                      variant="ghost"
                      onClick={() => handleCancelInvite(invite.id)}
                    >
                      Cancelar
                    </Button>
                  </div>
                ))}
              </CardContent>
            </Card>
          )}
        </>
      )}

      <Dialog
        open={memberToRemove !== null}
        onClose={() => setMemberToRemove(null)}
        title="Remover membro da equipe"
        description={
          memberToRemove
            ? `${memberToRemove.name} vai perder o acesso ao painel administrativo desse negócio.`
            : undefined
        }
      >
        <div className="flex justify-end gap-2">
          <Button type="button" variant="outline" onClick={() => setMemberToRemove(null)} disabled={isRemoving}>
            Cancelar
          </Button>
          <Button type="button" variant="destructive" onClick={handleRemoveMember} disabled={isRemoving}>
            {isRemoving ? "Removendo..." : "Remover"}
          </Button>
        </div>
      </Dialog>
    </div>
  );
}

export default function EquipePage() {
  return (
    <RequireAuth role="admin">
      <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-10">
        <h1 className="mb-6 text-2xl font-semibold text-foreground">Equipe</h1>
        <EquipePanel />
      </main>
    </RequireAuth>
  );
}
