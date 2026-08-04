"use client";

import { useState, type FormEvent } from "react";
import useSWR from "swr";
import { PackageOpen, Pencil, Power, Trash2 } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Alert } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import {
  createService,
  deleteService,
  listAdminServices,
  updateService,
} from "@/lib/api/services";
import { listTeam } from "@/lib/api/team";
import type { Service } from "@/lib/types/services";
import { formatApiError } from "@/lib/utils/format-error";

const emptyForm = {
  name: "",
  description: "",
  duration_minutes: "30",
  price: "",
  staff_ids: [] as number[],
};

function formatPrice(price: number) {
  return price.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

function ServicosAdminPanel() {
  const {
    data: services,
    isLoading,
    mutate: reloadServices,
  } = useSWR("admin-services", listAdminServices);
  const { data: team } = useSWR("team", listTeam);
  const [form, setForm] = useState(emptyForm);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  function startEdit(service: Service) {
    setEditingId(service.id);
    setForm({
      name: service.name,
      description: service.description ?? "",
      duration_minutes: String(service.duration_minutes),
      price: String(service.price),
      staff_ids: service.staff.map((member) => member.id),
    });
  }

  function toggleStaff(memberId: number) {
    setForm((current) => ({
      ...current,
      staff_ids: current.staff_ids.includes(memberId)
        ? current.staff_ids.filter((id) => id !== memberId)
        : [...current.staff_ids, memberId],
    }));
  }

  function cancelEdit() {
    setEditingId(null);
    setForm(emptyForm);
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setIsSubmitting(true);

    const payload = {
      name: form.name,
      description: form.description || undefined,
      duration_minutes: Number(form.duration_minutes),
      price: Number(form.price),
      staff_ids: form.staff_ids,
    };

    try {
      if (editingId) {
        await updateService(editingId, payload);
      } else {
        await createService(payload);
      }
      cancelEdit();
      reloadServices();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function toggleActive(service: Service) {
    await updateService(service.id, { active: !service.active });
    reloadServices();
  }

  async function handleDelete(service: Service) {
    if (!confirm(`Excluir o serviço "${service.name}"?`)) return;
    await deleteService(service.id);
    reloadServices();
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[360px_1fr]">
      <Card className="h-fit">
        <CardHeader>
          <CardTitle className="text-base">{editingId ? "Editar serviço" : "Novo serviço"}</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            {error && <Alert variant="destructive">{error}</Alert>}

            <div>
              <Label htmlFor="name">Nome</Label>
              <Input
                id="name"
                required
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
              />
            </div>

            <div>
              <Label htmlFor="description">Descrição</Label>
              <Textarea
                id="description"
                value={form.description}
                onChange={(e) => setForm({ ...form, description: e.target.value })}
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label htmlFor="duration">Duração (min)</Label>
                <Input
                  id="duration"
                  type="number"
                  min={5}
                  required
                  value={form.duration_minutes}
                  onChange={(e) => setForm({ ...form, duration_minutes: e.target.value })}
                />
              </div>
              <div>
                <Label htmlFor="price">Preço (R$)</Label>
                <Input
                  id="price"
                  type="number"
                  min={0}
                  step="0.01"
                  required
                  value={form.price}
                  onChange={(e) => setForm({ ...form, price: e.target.value })}
                />
              </div>
            </div>

            {team && team.members.length > 0 && (
              <div>
                <Label>Profissionais (opcional)</Label>
                <p className="mb-2 text-xs text-muted-foreground">
                  Se escolher alguém, o cliente precisa selecionar o profissional pra agendar esse serviço.
                </p>
                <div className="flex flex-col gap-1.5">
                  {team.members.map((member) => (
                    <label
                      key={member.id}
                      className="flex items-center gap-2 text-sm text-foreground"
                    >
                      <input
                        type="checkbox"
                        checked={form.staff_ids.includes(member.id)}
                        onChange={() => toggleStaff(member.id)}
                      />
                      {member.name}
                    </label>
                  ))}
                </div>
              </div>
            )}

            <div className="flex gap-2">
              <Button type="submit" disabled={isSubmitting} className="flex-1">
                {editingId ? "Salvar alterações" : "Criar serviço"}
              </Button>
              {editingId && (
                <Button type="button" variant="secondary" onClick={cancelEdit}>
                  Cancelar
                </Button>
              )}
            </div>
          </form>
        </CardContent>
      </Card>

      <div className="flex flex-col gap-3">
        {isLoading &&
          Array.from({ length: 3 }).map((_, index) => (
            <Card key={index}>
              <CardContent className="flex flex-col items-start gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-col gap-2">
                  <Skeleton className="h-5 w-40" />
                  <Skeleton className="h-3.5 w-28" />
                </div>
                <div className="flex gap-2">
                  <Skeleton className="h-8 w-20" />
                  <Skeleton className="h-8 w-24" />
                  <Skeleton className="h-8 w-20" />
                </div>
              </CardContent>
            </Card>
          ))}

        {!isLoading && services?.length === 0 && (
          <EmptyState
            icon={PackageOpen}
            title="Nenhum serviço cadastrado"
            description="Crie o primeiro serviço usando o formulário ao lado."
          />
        )}

        {services?.map((service) => (
          <Card key={service.id}>
            <CardContent className="flex flex-col items-start gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-medium text-foreground">{service.name}</span>
                  <Badge variant={service.active ? "success" : "secondary"}>
                    {service.active ? "Ativo" : "Inativo"}
                  </Badge>
                </div>
                <p className="text-sm text-muted-foreground">
                  {service.duration_minutes} min · {formatPrice(service.price)}
                </p>
                {service.staff.length > 0 && (
                  <p className="text-sm text-muted-foreground">
                    Profissionais: {service.staff.map((member) => member.name).join(", ")}
                  </p>
                )}
              </div>

              <div className="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" onClick={() => startEdit(service)}>
                  <Pencil className="h-4 w-4" />
                  Editar
                </Button>
                <Button variant="secondary" size="sm" onClick={() => toggleActive(service)}>
                  <Power className="h-4 w-4" />
                  {service.active ? "Desativar" : "Ativar"}
                </Button>
                <Button variant="destructive" size="sm" onClick={() => handleDelete(service)}>
                  <Trash2 className="h-4 w-4" />
                  Excluir
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}

export default function ServicosAdminPage() {
  return (
    <RequireAuth role="admin">
      <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-10">
        <h1 className="mb-6 text-2xl font-semibold text-foreground">Serviços</h1>
        <ServicosAdminPanel />
      </main>
    </RequireAuth>
  );
}
