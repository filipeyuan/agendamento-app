import { API_URL } from "@/lib/api/client";

interface HealthResponse {
  status: "ok";
  timestamp: string;
}

export async function getApiHealth() {
  try {
    const res = await fetch(`${API_URL}/health`, { next: { revalidate: 30 } });
    if (!res.ok) return null;
    return (await res.json()) as HealthResponse;
  } catch {
    return null;
  }
}
