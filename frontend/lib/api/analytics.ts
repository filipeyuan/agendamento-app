import { apiRequest } from "@/lib/api/client";
import type { AnalyticsSummary } from "@/lib/types/analytics";

export function getAnalyticsSummary(days: number) {
  return apiRequest<AnalyticsSummary>(`/admin/analytics?days=${days}`);
}
