export function SchedulingIllustration({ className }: { className?: string }) {
  return (
    <svg
      viewBox="0 0 400 340"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      className={className}
      role="img"
      aria-label="Ilustração de um calendário de agendamentos"
    >
      <rect x="56" y="46" width="288" height="248" rx="20" fill="var(--card)" stroke="var(--border)" strokeWidth="2" />

      <path d="M56 90a20 20 0 0 1 20-20h248a20 20 0 0 1 20 20v26H56V90Z" fill="var(--primary)" />
      <rect x="112" y="34" width="14" height="34" rx="7" fill="var(--primary)" />
      <rect x="274" y="34" width="14" height="34" rx="7" fill="var(--primary)" />

      <g opacity="0.9">
        <rect x="86" y="140" width="34" height="34" rx="8" fill="var(--muted)" />
        <rect x="132" y="140" width="34" height="34" rx="8" fill="var(--warning)" opacity="0.55" />
        <rect x="178" y="140" width="34" height="34" rx="8" fill="var(--muted)" />
        <rect x="224" y="140" width="34" height="34" rx="8" fill="var(--success)" opacity="0.55" />
        <rect x="270" y="140" width="34" height="34" rx="8" fill="var(--muted)" />

        <rect x="86" y="186" width="34" height="34" rx="8" fill="var(--primary)" opacity="0.6" />
        <rect x="132" y="186" width="34" height="34" rx="8" fill="var(--muted)" />
        <rect x="178" y="186" width="34" height="34" rx="8" fill="var(--muted)" />
        <rect x="224" y="186" width="34" height="34" rx="8" fill="var(--muted)" />
        <rect x="270" y="186" width="34" height="34" rx="8" fill="var(--destructive)" opacity="0.45" />

        <rect x="86" y="232" width="34" height="34" rx="8" fill="var(--muted)" />
        <rect x="132" y="232" width="34" height="34" rx="8" fill="var(--muted)" />
        <rect x="178" y="232" width="34" height="34" rx="8" fill="var(--success)" opacity="0.55" />
        <rect x="224" y="232" width="34" height="34" rx="8" fill="var(--muted)" />
        <rect x="270" y="232" width="34" height="34" rx="8" fill="var(--muted)" />
      </g>

      <circle cx="308" cy="252" r="40" fill="var(--success)" stroke="var(--card)" strokeWidth="6" />
      <path
        d="M291 253l11 11 20-22"
        stroke="var(--success-foreground)"
        strokeWidth="6"
        strokeLinecap="round"
        strokeLinejoin="round"
        fill="none"
      />

      <circle cx="62" cy="52" r="32" fill="var(--card)" stroke="var(--primary)" strokeWidth="4" />
      <path
        d="M62 35v17l11 9"
        stroke="var(--primary)"
        strokeWidth="5"
        strokeLinecap="round"
        strokeLinejoin="round"
        fill="none"
      />
    </svg>
  );
}
