"use client";

import { useState } from "react";
import { Mail, X } from "lucide-react";

import { resendVerificationEmail } from "@/lib/api/auth";
import { useAuth } from "@/lib/auth/context";
import { formatApiError } from "@/lib/utils/format-error";

export function EmailVerificationBanner() {
  const { user } = useAuth();
  const [dismissed, setDismissed] = useState(false);
  const [isSending, setIsSending] = useState(false);
  const [feedback, setFeedback] = useState<string | null>(null);

  if (!user || user.email_verified || dismissed) {
    return null;
  }

  async function handleResend() {
    setIsSending(true);
    setFeedback(null);

    try {
      await resendVerificationEmail();
      setFeedback("E-mail reenviado. Confira sua caixa de entrada.");
    } catch (err) {
      setFeedback(formatApiError(err));
    } finally {
      setIsSending(false);
    }
  }

  return (
    <div className="flex items-center justify-between gap-4 border-b border-warning/30 bg-warning/10 px-4 py-2.5 text-sm text-foreground">
      <div className="flex items-center gap-2">
        <Mail className="h-4 w-4 shrink-0 text-warning" />
        <span>{feedback ?? "Confirme seu e-mail pra garantir o acesso à sua conta."}</span>
        {!feedback && (
          <button
            type="button"
            onClick={handleResend}
            disabled={isSending}
            className="cursor-pointer font-medium text-primary hover:underline disabled:cursor-not-allowed disabled:opacity-50"
          >
            {isSending ? "Enviando..." : "Reenviar e-mail"}
          </button>
        )}
      </div>
      <button
        type="button"
        onClick={() => setDismissed(true)}
        aria-label="Fechar"
        className="cursor-pointer rounded-full p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
      >
        <X className="h-4 w-4" />
      </button>
    </div>
  );
}
