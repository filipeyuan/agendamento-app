import type { Business } from "@/lib/types/businesses";

export type UserRole = "client" | "admin";

export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  phone: string | null;
  business?: Business | null;
}
