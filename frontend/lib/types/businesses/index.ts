export type BusinessPlan = "free" | "pro";

export interface Business {
  id: number;
  name: string;
  slug: string;
  plan: BusinessPlan;
  premium_prompt_due: boolean;
}
