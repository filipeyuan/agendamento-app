import { apiRequest } from "@/lib/api/client";

export async function createBillingCheckout() {
  const { checkout_url } = await apiRequest<{ checkout_url: string }>("/admin/billing/checkout", {
    method: "POST",
  });
  return { checkoutUrl: checkout_url };
}

export async function createBillingPortalSession() {
  const { portal_url } = await apiRequest<{ portal_url: string }>("/admin/billing/portal", {
    method: "POST",
  });
  return { portalUrl: portal_url };
}

export function dismissPremiumPrompt() {
  return apiRequest<{ message: string }>("/admin/billing/dismiss-premium-prompt", {
    method: "POST",
  });
}
