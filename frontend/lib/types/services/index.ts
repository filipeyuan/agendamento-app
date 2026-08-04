import type { TeamMember } from "@/lib/types/team";

export interface Service {
  id: number;
  name: string;
  description: string | null;
  duration_minutes: number;
  price: number;
  active: boolean;
  staff: TeamMember[];
  created_at: string;
}
