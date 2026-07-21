import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { SWRConfig } from "swr";

import AgendamentosAdminPage from "@/app/admin/agendamentos/page";
import { adminAppointments, updateAppointmentStatus } from "@/lib/api/appointments";
import type { Appointment } from "@/lib/types/appointments";

jest.mock("@/components/auth/require-auth.component", () => ({
  RequireAuth: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

jest.mock("@/lib/api/appointments");

function MockFullCalendar({
  events,
  eventClick,
  datesSet,
}: {
  events: { id: string; title: string }[];
  eventClick: (arg: { event: { id: string } }) => void;
  datesSet: (arg: { start: Date; end: Date }) => void;
}) {
  const { useEffect } = jest.requireActual("react");

  useEffect(() => {
    datesSet({
      start: new Date("2026-07-01T00:00:00-03:00"),
      end: new Date("2026-08-01T00:00:00-03:00"),
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps -- fires once, mirrors FullCalendar's real initial datesSet call
  }, []);

  return (
    <div>
      {events.map((event) => (
        <button key={event.id} onClick={() => eventClick({ event: { id: event.id } })}>
          {event.title}
        </button>
      ))}
    </div>
  );
}

jest.mock("@fullcalendar/react", () => ({
  __esModule: true,
  default: MockFullCalendar,
}));

jest.mock("@fullcalendar/core/locales/pt-br", () => ({}));
jest.mock("@fullcalendar/daygrid", () => ({}));
jest.mock("@fullcalendar/timegrid", () => ({}));
jest.mock("@fullcalendar/list", () => ({}));
jest.mock("@fullcalendar/interaction", () => ({}));

const mockedAdminAppointments = jest.mocked(adminAppointments);
const mockedUpdateAppointmentStatus = jest.mocked(updateAppointmentStatus);

function renderPage() {
  return render(
    <SWRConfig value={{ provider: () => new Map() }}>
      <AgendamentosAdminPage />
    </SWRConfig>
  );
}

function buildAppointment(overrides: Partial<Appointment>): Appointment {
  return {
    id: 1,
    status: "pending",
    source: "web",
    start_at: "2026-07-22T10:00:00-03:00",
    end_at: "2026-07-22T10:30:00-03:00",
    notes: null,
    service: {
      id: 1,
      name: "Corte de cabelo",
      description: null,
      duration_minutes: 30,
      price: 40,
      active: true,
      created_at: "2026-01-01T00:00:00-03:00",
    },
    client: { id: 2, name: "Cliente Teste", email: "cliente@example.com" },
    created_at: "2026-07-01T00:00:00-03:00",
    ...overrides,
  };
}

describe("AgendamentosAdminPage", () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it("fetches appointments for the visible calendar range and lists them as events", async () => {
    mockedAdminAppointments.mockResolvedValue([buildAppointment({})]);

    renderPage();

    expect(await screen.findByText(/corte de cabelo · cliente teste/i)).toBeInTheDocument();
    expect(mockedAdminAppointments).toHaveBeenCalledWith({
      from: "2026-07-01",
      to: "2026-08-01",
      status: undefined,
    });
  });

  it("shows the appointment detail and confirms it when an event is clicked", async () => {
    mockedAdminAppointments.mockResolvedValue([buildAppointment({})]);
    mockedUpdateAppointmentStatus.mockResolvedValue(buildAppointment({ status: "confirmed" }));
    const user = userEvent.setup();

    renderPage();

    await user.click(await screen.findByText(/corte de cabelo · cliente teste/i));

    expect(screen.getByText("Pendente", { selector: "span" })).toBeInTheDocument();
    expect(screen.getByText(/cliente@example.com/i)).toBeInTheDocument();

    await user.click(screen.getByRole("button", { name: /confirmar/i }));

    await waitFor(() => {
      expect(mockedUpdateAppointmentStatus).toHaveBeenCalledWith(1, "confirmed");
    });
  });

  it("shows an empty state when there are no appointments in the range", async () => {
    mockedAdminAppointments.mockResolvedValue([]);

    renderPage();

    expect(await screen.findByText(/nenhum agendamento neste período/i)).toBeInTheDocument();
  });
});
