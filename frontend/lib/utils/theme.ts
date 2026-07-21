export type Theme = "light" | "dark";

const THEME_KEY = "agendamento_theme";
const THEME_EVENT = "agendamento-theme-change";

export function getStoredTheme(): Theme | null {
  const value = localStorage.getItem(THEME_KEY);
  return value === "light" || value === "dark" ? value : null;
}

export function applyTheme(theme: Theme) {
  document.documentElement.dataset.theme = theme;
  localStorage.setItem(THEME_KEY, theme);
  window.dispatchEvent(new Event(THEME_EVENT));
}

export function getActiveTheme(): Theme {
  const stored = getStoredTheme();
  if (stored) return stored;
  return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}

export function subscribeTheme(callback: () => void): () => void {
  const mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
  window.addEventListener(THEME_EVENT, callback);
  mediaQuery.addEventListener("change", callback);

  return () => {
    window.removeEventListener(THEME_EVENT, callback);
    mediaQuery.removeEventListener("change", callback);
  };
}

export const THEME_INIT_SCRIPT = `
(function () {
  try {
    var stored = localStorage.getItem("${THEME_KEY}");
    if (stored === "light" || stored === "dark") {
      document.documentElement.dataset.theme = stored;
    }
  } catch (e) {}
})();
`;
