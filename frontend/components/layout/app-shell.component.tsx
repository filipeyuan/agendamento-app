"use client";

import { useState } from "react";

import { AppTopBar } from "@/components/layout/app-topbar.component";
import { Sidebar } from "@/components/layout/sidebar.component";

export function AppShell({ children }: { children: React.ReactNode }) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  return (
    <div className="flex min-h-full flex-1">
      <Sidebar isOpen={isMenuOpen} onClose={() => setIsMenuOpen(false)} />

      <div className="flex min-w-0 flex-1 flex-col">
        <AppTopBar onMenuClick={() => setIsMenuOpen(true)} />
        {children}
      </div>
    </div>
  );
}
