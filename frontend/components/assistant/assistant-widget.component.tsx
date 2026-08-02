"use client";

import { useCallback, useRef, useState } from "react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { MessageCircle, X } from "lucide-react";

import { AssistantChat } from "@/components/assistant/assistant-chat.component";
import { useClickOutside } from "@/lib/hooks/use-click-outside";

export function AssistantWidget() {
  const [isOpen, setIsOpen] = useState(false);
  const shouldReduceMotion = useReducedMotion();
  const containerRef = useRef<HTMLDivElement>(null);

  useClickOutside(containerRef, useCallback(() => setIsOpen(false), []));

  return (
    <div ref={containerRef} className="fixed bottom-5 right-5 z-30 sm:bottom-6 sm:right-6">
      <AnimatePresence>
        {isOpen && (
          <motion.div
            initial={{ opacity: 0, y: shouldReduceMotion ? 4 : 16, scale: shouldReduceMotion ? 1 : 0.96 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: shouldReduceMotion ? 4 : 16, scale: shouldReduceMotion ? 1 : 0.96 }}
            transition={{ duration: shouldReduceMotion ? 0.15 : 0.25, ease: [0.16, 1, 0.3, 1] }}
            className="shadow-elevated-lg absolute bottom-[4.25rem] right-0 flex h-[32rem] max-h-[75vh] w-[23rem] max-w-[calc(100vw-2.5rem)] flex-col overflow-hidden rounded-2xl border border-border bg-card"
          >
            <AssistantChat className="h-full" onClose={() => setIsOpen(false)} />
          </motion.div>
        )}
      </AnimatePresence>

      <motion.button
        type="button"
        onClick={() => setIsOpen((open) => !open)}
        aria-label={isOpen ? "Fechar assistente" : "Abrir assistente"}
        aria-expanded={isOpen}
        whileHover={shouldReduceMotion ? undefined : { scale: 1.06 }}
        whileTap={shouldReduceMotion ? undefined : { scale: 0.96 }}
        className="shadow-elevated-lg flex h-14 w-14 cursor-pointer items-center justify-center rounded-full bg-primary text-primary-foreground"
      >
        <AnimatePresence mode="wait" initial={false}>
          <motion.span
            key={isOpen ? "close" : "open"}
            initial={{ opacity: 0, rotate: shouldReduceMotion ? 0 : -45 }}
            animate={{ opacity: 1, rotate: 0 }}
            exit={{ opacity: 0, rotate: shouldReduceMotion ? 0 : 45 }}
            transition={{ duration: shouldReduceMotion ? 0.1 : 0.2 }}
            className="flex"
          >
            {isOpen ? <X className="h-6 w-6" /> : <MessageCircle className="h-6 w-6" />}
          </motion.span>
        </AnimatePresence>
      </motion.button>
    </div>
  );
}
