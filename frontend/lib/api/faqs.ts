import { apiRequest } from "@/lib/api/client";
import type { Faq } from "@/lib/types/faqs";

interface ApiResource<T> {
  data: T;
}

export async function listFaqs() {
  const { data } = await apiRequest<ApiResource<Faq[]>>("/admin/faq");
  return data;
}

export async function createFaq(payload: { question: string; answer: string }) {
  const { data } = await apiRequest<ApiResource<Faq>>("/admin/faq", {
    method: "POST",
    body: JSON.stringify(payload),
  });
  return data;
}

export async function updateFaq(id: number, payload: { question: string; answer: string }) {
  const { data } = await apiRequest<ApiResource<Faq>>(`/admin/faq/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
  return data;
}

export function deleteFaq(id: number) {
  return apiRequest<void>(`/admin/faq/${id}`, { method: "DELETE" });
}
