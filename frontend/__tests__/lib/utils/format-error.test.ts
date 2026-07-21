import { ApiError } from "@/lib/api/client";
import { formatApiError } from "@/lib/utils/format-error";

describe("formatApiError", () => {
  it("joins validation errors from an ApiError", () => {
    const error = new ApiError("Dados inválidos", 422, {
      email: ["O campo email é obrigatório."],
      password: ["A senha deve ter pelo menos 8 caracteres."],
    });

    expect(formatApiError(error)).toBe(
      "O campo email é obrigatório. A senha deve ter pelo menos 8 caracteres."
    );
  });

  it("falls back to the ApiError message when there are no field errors", () => {
    const error = new ApiError("Esse horário acabou de ser ocupado. Escolha outro horário.", 409);

    expect(formatApiError(error)).toBe("Esse horário acabou de ser ocupado. Escolha outro horário.");
  });

  it("returns a generic message for errors that are not an ApiError", () => {
    expect(formatApiError(new Error("boom"))).toBe(
      "Não foi possível completar a ação. Tente novamente."
    );
  });
});
