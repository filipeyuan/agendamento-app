"use client";

import { useState, type FormEvent } from "react";
import useSWR from "swr";
import { MessageCircleQuestion, Pencil, Trash2 } from "lucide-react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { Textarea } from "@/components/ui/textarea";
import { createFaq, deleteFaq, listFaqs, updateFaq } from "@/lib/api/faqs";
import type { Faq } from "@/lib/types/faqs";
import { formatApiError } from "@/lib/utils/format-error";

const emptyForm = { question: "", answer: "" };

function FaqAdminPanel() {
  const { data: faqs, isLoading, mutate: reloadFaqs } = useSWR("admin-faqs", listFaqs);
  const [form, setForm] = useState(emptyForm);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  function startEdit(faq: Faq) {
    setEditingId(faq.id);
    setForm({ question: faq.question, answer: faq.answer });
  }

  function cancelEdit() {
    setEditingId(null);
    setForm(emptyForm);
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setIsSubmitting(true);

    try {
      if (editingId) {
        await updateFaq(editingId, form);
      } else {
        await createFaq(form);
      }
      cancelEdit();
      reloadFaqs();
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDelete(faq: Faq) {
    if (!confirm(`Excluir a pergunta "${faq.question}"?`)) return;
    await deleteFaq(faq.id);
    reloadFaqs();
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[360px_1fr]">
      <Card className="h-fit">
        <CardHeader>
          <CardTitle className="text-base">{editingId ? "Editar pergunta" : "Nova pergunta"}</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            {error && <Alert variant="destructive">{error}</Alert>}

            <div>
              <Label htmlFor="question">Pergunta</Label>
              <Textarea
                id="question"
                required
                value={form.question}
                onChange={(e) => setForm({ ...form, question: e.target.value })}
              />
            </div>

            <div>
              <Label htmlFor="answer">Resposta</Label>
              <Textarea
                id="answer"
                required
                rows={5}
                value={form.answer}
                onChange={(e) => setForm({ ...form, answer: e.target.value })}
              />
            </div>

            <p className="text-xs text-muted-foreground">
              O assistente de IA usa essas perguntas e respostas pra responder o que os clientes
              perguntarem sobre o seu negócio.
            </p>

            <div className="flex gap-2">
              <Button type="submit" disabled={isSubmitting} className="flex-1">
                {editingId ? "Salvar alterações" : "Adicionar pergunta"}
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
              <CardContent className="flex flex-col gap-2 py-4">
                <Skeleton className="h-5 w-52" />
                <Skeleton className="h-3.5 w-full" />
              </CardContent>
            </Card>
          ))}

        {!isLoading && faqs?.length === 0 && (
          <EmptyState
            icon={MessageCircleQuestion}
            title="Nenhuma pergunta cadastrada"
            description="Adicione perguntas frequentes usando o formulário ao lado."
          />
        )}

        {faqs?.map((faq) => (
          <Card key={faq.id}>
            <CardContent className="flex flex-col items-start gap-4 py-4 sm:flex-row sm:items-start sm:justify-between">
              <div className="min-w-0">
                <p className="font-medium text-foreground">{faq.question}</p>
                <p className="text-sm text-muted-foreground">{faq.answer}</p>
              </div>

              <div className="flex shrink-0 flex-wrap gap-2">
                <Button variant="outline" size="sm" onClick={() => startEdit(faq)}>
                  <Pencil className="h-4 w-4" />
                  Editar
                </Button>
                <Button variant="destructive" size="sm" onClick={() => handleDelete(faq)}>
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

export default function FaqAdminPage() {
  return (
    <RequireAuth role="admin">
      <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-10">
        <h1 className="mb-6 text-2xl font-semibold text-foreground">Perguntas frequentes</h1>
        <FaqAdminPanel />
      </main>
    </RequireAuth>
  );
}
