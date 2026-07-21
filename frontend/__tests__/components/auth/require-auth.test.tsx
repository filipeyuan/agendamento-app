import { render, screen, waitFor } from "@testing-library/react";

import { RequireAuth } from "@/components/auth/require-auth.component";
import { useAuth } from "@/lib/auth/context";

jest.mock("@/lib/auth/context");

const replace = jest.fn();
jest.mock("next/navigation", () => ({
  useRouter: () => ({ replace }),
}));

const mockedUseAuth = jest.mocked(useAuth);

function authState(overrides: Partial<ReturnType<typeof useAuth>>) {
  return {
    user: null,
    isLoading: false,
    login: jest.fn(),
    register: jest.fn(),
    logout: jest.fn(),
    ...overrides,
  };
}

describe("RequireAuth", () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it("shows a spinner instead of the content while the auth state is loading", () => {
    mockedUseAuth.mockReturnValue(authState({ isLoading: true }));

    render(
      <RequireAuth>
        <p>Conteúdo protegido</p>
      </RequireAuth>
    );

    expect(screen.queryByText("Conteúdo protegido")).not.toBeInTheDocument();
  });

  it("redirects to /login when there is no authenticated user", async () => {
    mockedUseAuth.mockReturnValue(authState({ user: null }));

    render(
      <RequireAuth>
        <p>Conteúdo protegido</p>
      </RequireAuth>
    );

    await waitFor(() => expect(replace).toHaveBeenCalledWith("/login"));
  });

  it("redirects to / when the user does not have the required role", async () => {
    mockedUseAuth.mockReturnValue(
      authState({ user: { id: 1, name: "Maria", email: "maria@example.com", role: "client", phone: null } })
    );

    render(
      <RequireAuth role="admin">
        <p>Conteúdo protegido</p>
      </RequireAuth>
    );

    await waitFor(() => expect(replace).toHaveBeenCalledWith("/"));
  });

  it("renders the children when the user has the required role", () => {
    mockedUseAuth.mockReturnValue(
      authState({ user: { id: 1, name: "Admin", email: "admin@example.com", role: "admin", phone: null } })
    );

    render(
      <RequireAuth role="admin">
        <p>Conteúdo protegido</p>
      </RequireAuth>
    );

    expect(screen.getByText("Conteúdo protegido")).toBeInTheDocument();
    expect(replace).not.toHaveBeenCalled();
  });
});
