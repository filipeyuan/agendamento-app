export interface TeamMember {
  id: number;
  name: string;
  email: string;
  created_at: string;
}

export interface PendingInvite {
  id: number;
  email: string;
  invited_by: string | null;
  expires_at: string;
  created_at: string;
}

export interface TeamOverview {
  members: TeamMember[];
  invites: PendingInvite[];
}
