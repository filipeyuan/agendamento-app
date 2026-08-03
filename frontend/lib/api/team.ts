import { apiRequest } from "@/lib/api/client";
import type { PendingInvite, TeamOverview } from "@/lib/types/team";

interface ApiResource<T> {
  data: T;
}

export function listTeam() {
  return apiRequest<TeamOverview>("/admin/team");
}

export async function inviteTeamMember(email: string) {
  const { data } = await apiRequest<ApiResource<PendingInvite>>("/admin/team/invite", {
    method: "POST",
    body: JSON.stringify({ email }),
  });
  return data;
}

export function cancelInvite(inviteId: number) {
  return apiRequest<{ message: string }>(`/admin/team/invites/${inviteId}`, { method: "DELETE" });
}

export function removeMember(memberId: number) {
  return apiRequest<{ message: string }>(`/admin/team/members/${memberId}`, { method: "DELETE" });
}
