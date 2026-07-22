import { apiRequest } from "@/lib/api/client";
import type { ChatMessage } from "@/lib/types/assistant";

export async function sendChatMessage(messages: ChatMessage[]) {
  const { message } = await apiRequest<{ message: string }>("/assistant/chat", {
    method: "POST",
    body: JSON.stringify({ messages }),
  });
  return message;
}
