"use client";

import { useRef, useState, type FormEvent } from "react";
import useSWR from "swr";
import { Send, Sparkles, User as UserIcon, X } from "lucide-react";

import { Alert } from "@/components/ui/alert";
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
    <div className={cn("flex items-start gap-3", isUser && "flex-row-reverse")}>
      <div
        className={cn(
          "flex h-8 w-8 shrink-0 items-center justify-center rounded-full",
          isUser ? "bg-primary text-primary-foreground" : "bg-primary/10 text-primary"
        )}
      >
        {isUser ? <UserIcon className="h-4 w-4" /> : <Sparkles className="h-4 w-4" />}
      </div>
      <div
        className={cn(
          "max-w-[75%] whitespace-pre-wrap rounded-2xl px-4 py-2.5 text-sm leading-relaxed",
          isUser ? "bg-primary text-primary-foreground" : "bg-muted text-foreground"
        )}
      >
        {message.content}
      </div>
    </div>
  );
}

export function AssistantChat({
  initialBusinessSlug = null,
  className,
  onClose,
}: {
  initialBusinessSlug?: string | null;
  className?: string;
  onClose?: () => void;
}) {
  // onClose só é passado no widget flutuante (painel estreito) - reaproveita
  // como sinal pra encolher o cabeçalho em vez de adicionar mais uma prop.
  const compact = Boolean(onClose);
  const { data: businesses } = useSWR("businesses", listBusinesses);

  const [businessOverride, setBusinessOverride] = useState<string | null>(initialBusinessSlug);
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
    <div className={cn("flex flex-col", className)}>
      <div
        className={cn(
          "flex items-center justify-between gap-2 border-b border-border px-4 sm:px-6",
          compact ? "py-3" : "py-3.5"
        )}
      >
        <div className="flex min-w-0 items-center gap-2.5">
          <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
            <Sparkles className="h-4.5 w-4.5" />
          </span>
          <div className="min-w-0">
            <p className="truncate text-sm font-semibold text-foreground">
              {compact ? "Assistente" : "Assistente de agendamento"}
            </p>
            {!compact && (
              <p className="truncate text-xs text-muted-foreground">
                Pergunte sobre serviços e horários
              </p>
            )}
          </div>
        </div>

        <div className="flex shrink-0 items-center gap-1.5">
          {businesses && businesses.length > 1 && (
            <Select
              aria-label="Negócio"
              className={compact ? "w-28" : "w-36 sm:w-44"}
              value={businessSlug ?? ""}
              onChange={(e) => setBusinessOverride(e.target.value)}
            >
              {businesses.map((business) => (
                <option key={business.id} value={business.slug}>
                  {business.name}
                </option>
              ))}
            </Select>
          )}

          {onClose && (
            <button
              type="button"
              onClick={onClose}
              aria-label="Fechar assistente"
              className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-muted-foreground hover:bg-accent"
            >
              <X className="h-4 w-4" />
            </button>
          )}
        </div>
      </div>

      <div className="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
        <div className="mx-auto flex max-w-2xl flex-col gap-5">
          {messages.map((message, index) => (
            <ChatBubble key={index} message={message} />
          ))}
          {isSending && (
            <div className="flex items-center gap-2 pl-11 text-sm text-muted-foreground">
              <Spinner className="h-4 w-4" />
              Digitando...
            </div>
          )}
          <div ref={bottomRef} />
        </div>
      </div>

      {error && (
        <div className="px-4 sm:px-6">
          <div className="mx-auto max-w-2xl">
            <Alert variant="destructive">{error}</Alert>
          </div>
        </div>
      )}

      <div className="border-t border-border px-4 py-4 sm:px-6">
        <form onSubmit={handleSubmit} className="mx-auto flex max-w-2xl items-end gap-2">
          <textarea
            className="max-h-32 min-h-[2.75rem] flex-1 resize-none rounded-2xl border border-input bg-background px-4 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
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
          <button
            type="submit"
            aria-label="Enviar"
            disabled={isSending || !input.trim() || !businessSlug}
            className="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-full bg-primary text-primary-foreground transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <Send className="h-4 w-4" />
          </button>
        </form>
      </div>
    </div>
  );
}
