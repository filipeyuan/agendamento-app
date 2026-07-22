import { getApiHealth } from "@/lib/api/health";
import { cn } from "@/lib/utils/cn";

export async function ApiStatus() {
  const health = await getApiHealth();

  return (
    <div className="flex items-center gap-2 text-xs text-muted-foreground">
      <span className={cn("h-2 w-2 rounded-full", health ? "bg-success" : "bg-destructive")} />
      API {health ? "online" : "offline"}
      {health && ` · última verificação ${new Date(health.timestamp).toLocaleTimeString("pt-BR")}`}
    </div>
  );
}
