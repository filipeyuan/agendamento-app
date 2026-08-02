import { AssistantWidget } from "@/components/assistant/assistant-widget.component";
import { Footer } from "@/components/layout/footer.component";
import { Navbar } from "@/components/layout/navbar.component";

export default function MarketingLayout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <Navbar />
      {children}
      <Footer />
      <AssistantWidget />
    </>
  );
}
