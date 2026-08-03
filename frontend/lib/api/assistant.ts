import { apiRequest } from "@/lib/api/client";
import type { ChatMessage } from "@/lib/types/assistant";

export async function sendChatMessage(messages: ChatMessage[], businessSlug: string) {
  const { message, assistant_quota_remaining } = await apiRequest<{
    message: string;
    assistant_quota_remaining: number | null;
  }>("/assistant/chat", {
    method: "POST",
    body: JSON.stringify({ messages, business: businessSlug }),
  });
  return { message, quotaRemaining: assistant_quota_remaining };
}
