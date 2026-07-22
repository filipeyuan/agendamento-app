export interface AnalyticsSummary {
  by_status: {
    pending: number;
    confirmed: number;
    cancelled: number;
    completed: number;
  };
  by_day: { date: string; count: number }[];
  revenue: number;
  top_services: { name: string; count: number }[];
}
