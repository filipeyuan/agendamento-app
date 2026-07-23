import { cn } from "@/lib/utils/cn";

export function Logo({ className, iconClassName }: { className?: string; iconClassName?: string }) {
  return (
    <div
      className={cn(
        "flex shrink-0 items-center justify-center rounded-lg",
        "h-7 w-7",
        className
      )}
      style={{ backgroundImage: "linear-gradient(135deg, var(--primary), var(--success))" }}
    >
      <svg
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        className={cn("h-4 w-4", iconClassName)}
        aria-hidden
      >
        <rect x="3.5" y="4.5" width="17" height="16" rx="3" stroke="white" strokeWidth="1.8" />
        <path d="M3.5 9.5h17" stroke="white" strokeWidth="1.8" />
        <path d="M8 2.5v3.5M16 2.5v3.5" stroke="white" strokeWidth="1.8" strokeLinecap="round" />
        <path d="M8 14l2.2 2.2L16 10.5" stroke="white" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    </div>
  );
}
