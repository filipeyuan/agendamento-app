import { apiFetch } from "@/lib/api/client";

type HealthResponse = {
  status: "ok";
  timestamp: string;
};

async function getApiHealth() {
  try {
    const res = await apiFetch("/health");
    if (!res.ok) return null;
    return (await res.json()) as HealthResponse;
  } catch {
    return null;
  }
}

export default async function Home() {
  const health = await getApiHealth();

  return (
    <div className="flex flex-1 items-center justify-center bg-zinc-50 dark:bg-black">
      <main className="flex w-full max-w-md flex-col items-center gap-4 rounded-2xl border border-black/10 bg-white p-10 text-center shadow-sm dark:border-white/10 dark:bg-zinc-900">
        <h1 className="text-2xl font-semibold text-zinc-900 dark:text-zinc-50">
          Agendamento App
        </h1>
        <p className="text-sm text-zinc-500 dark:text-zinc-400">
          Sistema de agendamento — Next.js + Laravel
        </p>

        <div className="mt-2 flex items-center gap-2 rounded-full border border-black/10 px-4 py-2 dark:border-white/10">
          <span
            className={`h-2.5 w-2.5 rounded-full ${
              health ? "bg-green-500" : "bg-red-500"
            }`}
          />
          <span className="text-sm font-medium text-zinc-700 dark:text-zinc-300">
            API {health ? "online" : "offline"}
          </span>
        </div>

        {health && (
          <p className="text-xs text-zinc-400">
            Última verificação: {new Date(health.timestamp).toLocaleString("pt-BR")}
          </p>
        )}
      </main>
    </div>
  );
}
