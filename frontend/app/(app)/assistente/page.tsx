"use client";

import { Suspense, useRef, useState, type FormEvent } from "react";
import { useSearchParams } from "next/navigation";
import useSWR from "swr";
import { Bot, Send, User as UserIcon } from "lucide-react";

import { Alert } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Select } from "@/components/ui/select";
import { Spinner } from "@/components/ui/spinner";
import { sendChatMessage } from "@/lib/api/assistant";
import { listBusinesses } from "@/lib/api/businesses";
import type { ChatMessage } from "@/lib/types/assistant";
import { formatApiError } from "@/lib/utils/format-error";
import { cn } from "@/lib/utils/cn";

const greeting: ChatMessage = {
  role: "assistant",
  content: "Oi! Me conta o que você precisa (serviço, dia e horário) que eu já te ajudo a agendar.",
};

function ChatBubble({ message }: { message: ChatMessage }) {
  const isUser = message.role === "user";

  return (
    <div className={cn("flex items-start gap-2", isUser && "flex-row-reverse")}>
      <div
        className={cn(
          "flex h-7 w-7 shrink-0 items-center justify-center rounded-full",
          isUser ? "bg-primary text-primary-foreground" : "bg-muted text-muted-foreground"
        )}
      >
        {isUser ? <UserIcon className="h-4 w-4" /> : <Bot className="h-4 w-4" />}
      </div>
      <div
        className={cn(
          "max-w-[75%] whitespace-pre-wrap rounded-lg px-3 py-2 text-sm",
          isUser ? "bg-primary text-primary-foreground" : "bg-muted text-foreground"
        )}
      >
        {message.content}
      </div>
    </div>
  );
}

function AssistantChat() {
  const searchParams = useSearchParams();
  const { data: businesses } = useSWR("businesses", listBusinesses);

  const [businessOverride, setBusinessOverride] = useState<string | null>(
    searchParams.get("business")
  );
  const businessSlug = businessOverride ?? businesses?.[0]?.slug ?? null;

  const [messages, setMessages] = useState<ChatMessage[]>([greeting]);
  const [input, setInput] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [isSending, setIsSending] = useState(false);
  const bottomRef = useRef<HTMLDivElement>(null);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    if (!input.trim() || isSending || !businessSlug) return;

    const nextMessages = [...messages, { role: "user", content: input.trim() } as ChatMessage];
    setMessages(nextMessages);
    setInput("");
    setError(null);
    setIsSending(true);

    try {
      const reply = await sendChatMessage(nextMessages, businessSlug);
      setMessages([...nextMessages, { role: "assistant", content: reply }]);
    } catch (err) {
      setError(formatApiError(err));
    } finally {
      setIsSending(false);
      setTimeout(() => bottomRef.current?.scrollIntoView({ behavior: "smooth" }), 50);
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Assistente de agendamento</CardTitle>
      </CardHeader>
      <CardContent className="flex flex-col gap-4">
        {businesses && businesses.length > 1 && (
          <div>
            <Label htmlFor="business">Negócio</Label>
            <Select
              id="business"
              value={businessSlug ?? ""}
              onChange={(e) => setBusinessOverride(e.target.value)}
            >
              {businesses.map((business) => (
                <option key={business.id} value={business.slug}>
                  {business.name}
                </option>
              ))}
            </Select>
          </div>
        )}

        <div className="flex max-h-[60vh] flex-col gap-3 overflow-y-auto rounded-lg border border-border p-4">
          {messages.map((message, index) => (
            <ChatBubble key={index} message={message} />
          ))}
          {isSending && (
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Spinner className="h-4 w-4" />
              Digitando...
            </div>
          )}
          <div ref={bottomRef} />
        </div>

        {error && <Alert variant="destructive">{error}</Alert>}

        <form onSubmit={handleSubmit} className="flex items-center gap-2">
          <textarea
            className="h-10 flex-1 resize-none rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            placeholder="Escreva sua mensagem..."
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                e.currentTarget.form?.requestSubmit();
              }
            }}
          />
          <Button type="submit" disabled={isSending || !input.trim() || !businessSlug}>
            <Send className="h-4 w-4" />
            Enviar
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}

export default function AssistentePage() {
  return (
    <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-10">
      <h1 className="mb-6 text-2xl font-semibold text-foreground">Assistente de agendamento</h1>
      <Suspense>
        <AssistantChat />
      </Suspense>
    </main>
  );
}
