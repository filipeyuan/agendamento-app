export interface BusinessHour {
  day_of_week: number;
  is_open: boolean;
  start_time: string | null;
  end_time: string | null;
}

export interface ScheduleBlock {
  id: number;
  date: string;
  start_time: string | null;
  end_time: string | null;
  reason: string | null;
}
