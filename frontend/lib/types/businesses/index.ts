export type BusinessPlan = "free" | "pro";

export interface Business {
  id: number;
  name: string;
  slug: string;
  plan: BusinessPlan;
  premium_prompt_due: boolean;
  logo_url: string | null;
  banner_url: string | null;
  accent_color: string | null;
}
