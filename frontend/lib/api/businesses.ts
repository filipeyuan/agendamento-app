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

export async function updateBusinessLogo(logo: File) {
  const formData = new FormData();
  formData.append("logo", logo);

  const { data } = await apiRequest<ApiResource<Business>>("/admin/business/logo", {
    method: "POST",
    body: formData,
  });
  return data;
}

export async function removeBusinessLogo() {
  const { data } = await apiRequest<ApiResource<Business>>("/admin/business/logo", {
    method: "DELETE",
  });
  return data;
}

export async function updateBusinessBanner(banner: File) {
  const formData = new FormData();
  formData.append("banner", banner);

  const { data } = await apiRequest<ApiResource<Business>>("/admin/business/banner", {
    method: "POST",
    body: formData,
  });
  return data;
}

export async function removeBusinessBanner() {
  const { data } = await apiRequest<ApiResource<Business>>("/admin/business/banner", {
    method: "DELETE",
  });
  return data;
}

export async function updateBusinessAccentColor(accentColor: string | null) {
  const { data } = await apiRequest<ApiResource<Business>>("/admin/business/accent-color", {
    method: "PUT",
    body: JSON.stringify({ accent_color: accentColor }),
  });
  return data;
}
