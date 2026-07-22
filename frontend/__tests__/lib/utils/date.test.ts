import { toLocalIsoDate, todayIsoDate } from "@/lib/utils/date";

describe("todayIsoDate", () => {
  afterEach(() => {
    jest.useRealTimers();
  });

  it("returns today's date in the local timezone", () => {
    jest.useFakeTimers().setSystemTime(new Date("2026-03-10T15:00:00-03:00"));

    expect(todayIsoDate()).toBe("2026-03-10");
  });

  it("stays on the local calendar day near midnight, unlike a UTC-based date", () => {
    jest.useFakeTimers().setSystemTime(new Date("2026-03-10T23:30:00-03:00"));

    expect(todayIsoDate()).toBe("2026-03-10");
  });
});

describe("toLocalIsoDate", () => {
  it("formats a given date using its local calendar day, not the UTC day", () => {
    expect(toLocalIsoDate(new Date("2026-03-10T23:30:00-03:00"))).toBe("2026-03-10");
  });
});
