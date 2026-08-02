"use client";

import { Suspense } from "react";
import { useSearchParams } from "next/navigation";

import { AssistantChat } from "@/components/assistant/assistant-chat.component";

function AssistentePageContent() {
  const searchParams = useSearchParams();

  return (
    <AssistantChat
      initialBusinessSlug={searchParams.get("business")}
      className="h-[calc(100dvh-3.5rem)]"
    />
  );
}

export default function AssistentePage() {
  return (
    <Suspense>
      <AssistentePageContent />
    </Suspense>
  );
}
