import { ApiError } from "@/lib/api/client";

export function formatApiError(error: unknown): string {
  if (error instanceof ApiError) {
    if (error.errors) {
      return Object.values(error.errors).flat().join(" ");
    }
    return error.message;
  }
  return "Não foi possível completar a ação. Tente novamente.";
}
