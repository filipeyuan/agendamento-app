export type UserRole = "client" | "admin";

export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  phone: string | null;
}
