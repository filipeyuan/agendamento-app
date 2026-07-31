import type { Metadata } from "next";
import { Inter, Space_Grotesk } from "next/font/google";
import { SWRConfig } from "swr";

import { Footer } from "@/components/layout/footer.component";
import { Navbar } from "@/components/layout/navbar.component";
import { AuthProvider } from "@/lib/auth/context";
import { THEME_INIT_SCRIPT } from "@/lib/utils/theme";

import "@daypicker/react/style.css";
import "./globals.css";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
});

const spaceGrotesk = Space_Grotesk({
  variable: "--font-space-grotesk",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "Zelo",
  description: "Zelo: agendamento online para negócios de serviço, sem conflito de horário.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="pt-BR"
      className={`${inter.variable} ${spaceGrotesk.variable} h-full antialiased`}
      suppressHydrationWarning
    >
      <head>
        <script dangerouslySetInnerHTML={{ __html: THEME_INIT_SCRIPT }} />
      </head>
      <body className="min-h-full flex flex-col">
        <SWRConfig
          value={{
            // O backend hiberna no plano grátis do Render e demora a acordar; sem isso o
            // SWR desiste rápido demais e mostra erro antes do servidor ter tempo de subir.
            errorRetryInterval: 3000,
            errorRetryCount: 8,
          }}
        >
          <AuthProvider>
            <Navbar />
            {children}
            <Footer />
          </AuthProvider>
        </SWRConfig>
      </body>
    </html>
  );
}
