import { apiRequest } from "@/lib/api/client";
import type { Business } from "@/lib/types/businesses";

interface ApiResource<T> {
  data: T;
}

export async function listBusinesses() {
  const { data } = await apiRequest<ApiResource<Business[]>>("/businesses");
  return data;
}

export async function getBusiness(slug: string) {
  const { data } = await apiRequest<ApiResource<Business>>(`/businesses/${slug}`);
  return data;
}
