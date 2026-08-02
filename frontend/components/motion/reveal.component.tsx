"use client";

import { motion, useReducedMotion, type Transition } from "motion/react";

type RevealProps = {
  children: React.ReactNode;
  className?: string;
  delay?: number;
  y?: number;
};

export function Reveal({ children, className, delay = 0, y = 24 }: RevealProps) {
  const shouldReduceMotion = useReducedMotion();

  // Reduced motion evita deslocamento grande/continuo (o que pode incomodar),
  // mas mantem um fade curto - sem isso o conteudo so "aparece" sem transicao
  // nenhuma, o que lia como "página estática" pra quem tem essa preferência
  // do SO/navegador ligada sem saber que ela também afeta sites.
  const transition: Transition = { duration: shouldReduceMotion ? 0.4 : 0.6, delay, ease: [0.16, 1, 0.3, 1] };

  return (
    <motion.div
      className={className}
      initial={{ opacity: 0, y: shouldReduceMotion ? 8 : y }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-80px" }}
      transition={transition}
    >
      {children}
    </motion.div>
  );
}
