import { RequireAuth } from "@/components/auth/require-auth.component";
import { AppShell } from "@/components/layout/app-shell.component";

export default function AppGroupLayout({ children }: { children: React.ReactNode }) {
  return (
    <AppShell>
      <RequireAuth>{children}</RequireAuth>
    </AppShell>
  );
}
