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
        <path
          d="M6.5 6.5h11L6.5 17.5h11"
          stroke="white"
          strokeWidth="2.6"
          strokeLinecap="round"
          strokeLinejoin="round"
        />
      </svg>
    </div>
  );
}
