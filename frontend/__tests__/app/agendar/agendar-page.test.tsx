import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { SWRConfig } from "swr";

import AgendarPage from "@/app/(app)/agendar/page";
import { ApiError } from "@/lib/api/client";
import { createAppointment } from "@/lib/api/appointments";
import { availableSlots, listServices } from "@/lib/api/services";
import { redirectTo } from "@/lib/utils/navigate";

jest.mock("@/components/auth/require-auth.component", () => ({
  RequireAuth: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

jest.mock("@/lib/api/services");
jest.mock("@/lib/api/appointments");
jest.mock("@/lib/utils/navigate");

jest.mock("next/navigation", () => ({
  useSearchParams: () => new URLSearchParams({ business: "studio-zelo" }),
}));

const mockedListServices = jest.mocked(listServices);
const mockedAvailableSlots = jest.mocked(availableSlots);
const mockedCreateAppointment = jest.mocked(createAppointment);
const mockedRedirectTo = jest.mocked(redirectTo);

function renderPage() {
  return render(
    <SWRConfig value={{ provider: () => new Map() }}>
      <AgendarPage />
    </SWRConfig>
  );
}

describe("AgendarPage", () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockedListServices.mockResolvedValue([
      {
        id: 1,
        name: "Corte de cabelo",
        description: null,
        duration_minutes: 30,
        price: 40,
        active: true,
        created_at: "2026-01-01T00:00:00-03:00",
      },
    ]);
    mockedAvailableSlots.mockResolvedValue([
      "2026-08-10T10:00:00-03:00",
      "2026-08-10T10:30:00-03:00",
    ]);
  });

  it("lists the available slots for the selected service and date", async () => {
    renderPage();

    expect(await screen.findByText("10:00")).toBeInTheDocument();
    expect(screen.getByText("10:30")).toBeInTheDocument();
  });

  it("books the selected slot and redirects to the Stripe checkout", async () => {
    mockedCreateAppointment.mockResolvedValue({
      checkoutUrl: "https://checkout.stripe.com/pay/cs_test_123",
    });
    const user = userEvent.setup();

    renderPage();

    await user.click(await screen.findByText("10:00"));
    await user.click(screen.getByRole("button", { name: /pagar e agendar/i }));

    await waitFor(() => {
      expect(mockedCreateAppointment).toHaveBeenCalledWith({
        service_id: 1,
        start_at: "2026-08-10T10:00:00-03:00",
        notes: undefined,
      });
    });
    await waitFor(() => {
      expect(mockedRedirectTo).toHaveBeenCalledWith("https://checkout.stripe.com/pay/cs_test_123");
    });
  });

  it("shows an error and clears the selected slot when the booking conflicts", async () => {
    mockedCreateAppointment.mockRejectedValue(
      new ApiError("Esse horário acabou de ser ocupado. Escolha outro horário.", 409)
    );
    const user = userEvent.setup();

    renderPage();

    await user.click(await screen.findByText("10:00"));
    await user.click(screen.getByRole("button", { name: /pagar e agendar/i }));

    expect(await screen.findByText(/esse horário acabou de ser ocupado/i)).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /pagar e agendar/i })).toBeDisabled();
  });

  it("shows a message and no slot buttons when there are no free slots", async () => {
    mockedAvailableSlots.mockResolvedValue([]);

    renderPage();

    expect(await screen.findByText(/nenhum horário livre/i)).toBeInTheDocument();
  });
});
