import { apiRequest } from "@/lib/api/client";
import type { ChatMessage } from "@/lib/types/assistant";

export async function sendChatMessage(messages: ChatMessage[], businessSlug: string) {
  const { message } = await apiRequest<{ message: string }>("/assistant/chat", {
    method: "POST",
    body: JSON.stringify({ messages, business: businessSlug }),
  });
  return message;
}
