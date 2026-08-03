import { apiRequest } from "@/lib/api/client";
import type { User } from "@/lib/types/users";

interface InvitePreview {
  email: string;
  business_name: string;
  expires_at: string;
}

interface AcceptInviteResponse {
  user: User;
  token: string;
}

export function getInvitePreview(token: string) {
  return apiRequest<InvitePreview>(`/invites/${token}`);
}

export function acceptInvite(payload: {
  token: string;
  name: string;
  password: string;
  password_confirmation: string;
}) {
  return apiRequest<AcceptInviteResponse>("/invites/accept", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}
