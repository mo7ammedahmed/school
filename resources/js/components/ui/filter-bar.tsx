import * as React from 'react';
import { cn } from '@/lib/utils';

interface FilterBarProps extends React.HTMLAttributes<HTMLDivElement> {
  className?: string;
  children: React.ReactNode;
}

interface FilterBarItemProps extends React.HTMLAttributes<HTMLDivElement> {
  className?: string;
}

export function FilterBar({
  className,
  children,
}: FilterBarProps) {
  return (
    <div
      className={cn(
        'flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between',
        className
      )}
    >
      {children}
    </div>
  );
}

FilterBar.displayName = 'FilterBar';

export function FilterBarItem({
  className,
  children,
}: FilterBarItemProps) {
  return (
    <div className={cn('flex flex-col gap-2', className)}>
      {children}
    </div>
  );
}

FilterBarItem.displayName = 'FilterBar.Item';

interface FilterBarSearchProps extends React.InputHTMLAttributes<HTMLInputElement> {
  className?: string;
  placeholder?: string;
}

export function FilterBarSearch({
  className,
  placeholder = 'Search...',
  ...props
}: FilterBarSearchProps) {
  return (
    <div className="relative w-full">
      <input
        type="search"
        placeholder={placeholder}
        className={cn(
          'flex h-11 w-full rounded-lg border border-input bg-card px-3.5 text-sm text-foreground',
          'shadow-[inset_0_1px_2px_rgba(28,26,22,0.03)] transition-[border-color,box-shadow] duration-200',
          'placeholder:text-muted-foreground/70',
          'focus-visible:outline-none focus-visible:border-ring/50 focus-visible:ring-[3px] focus-visible:ring-ring/15',
          'disabled:cursor-not-allowed disabled:opacity-55',
          className
        )}
        {...props}
      />
    </div>
  );
}

FilterBarSearch.displayName = 'FilterBar.Search';

interface FilterBarSelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  className?: string;
  label?: string;
}

export function FilterBarSelect({
  className,
  label,
  children,
  ...props
}: FilterBarSelectProps) {
  return (
    <div className="w-full">
      {label && (
        <label htmlForm={label?.toLowerCase().replace(/\s+/g, '-')} className="mb-1.5 block text-sm font-semibold text-foreground">
          {label}
        </label>
      )}
      <div className="relative">
        <select
          className={cn(
            'flex h-11 w-full appearance-none rounded-lg border border-input bg-card px-3.5 text-sm text-foreground',
            'shadow-[inset_0_1px_2px_rgba(28,26,22,0.03)] transition-[border-color,box-shadow] duration-200',
            'focus-visible:outline-none focus-visible:border-ring/50 focus-visible:ring-[3px] focus-visible:ring-ring/15',
            'disabled:cursor-not-allowed disabled:opacity-55',
            className
          )}
          {...props}
        >
          {children}
        </select>
      </div>
    </div>
  );
}

FilterBarSelect.displayName = 'FilterBar.Select';

interface FilterBarButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  className?: string;
  variant?: 'default' | 'secondary' | 'outline' | 'ghost' | 'destructive' | 'link';
  size?: 'sm' | 'md' | 'lg' | 'icon' | 'icon-sm';
}

export function FilterBarButton({
  className,
  variant = 'default',
  size = 'md',
  children,
  ...props
}: FilterBarButtonProps) {
  return (
    <button
      className={cn(
        [
          'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg font-medium',
          'tracking-[-0.006em] transition-all duration-200 ease-out',
          'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/60 focus-visible:ring-offset-2',
          'disabled:pointer-events-none disabled:opacity-50',
          '[&_svg]:pointer-events-none [&_svg]:shrink-0',
          'active:translate-y-px',
        ].join(' '),
        {
          default: [
            'bg-primary text-primary-foreground shadow-[inset_0_1px_0_rgba(255,255,255,0.12),0_1px_2px_rgba(6,40,30,0.28)]',
            'hover:bg-primary/92 hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.12),0_2px_6px_rgba(6,40,30,0.3)]',
          ],
          secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/85',
          outline: [
            'border border-input bg-card/60 text-foreground shadow-[inset_0_0_0_1px_rgba(255,255,255,0.4)]',
            'hover:border-foreground/20 hover:bg-accent',
          ],
          ghost: 'text-muted-foreground hover:bg-accent hover:text-foreground',
          destructive: 'bg-destructive text-white shadow-sm hover:bg-destructive/90',
          link: 'text-primary underline-offset-4 hover:underline',
        }[variant],
        {
          sm: 'h-9 px-3 text-sm',
          md: 'h-10 px-4 text-sm',
          lg: 'h-12 px-6 text-base',
          icon: 'size-10',
          'icon-sm': 'size-9',
        }[size]
      )}
      {...props}
    >
      {children}
    </button>
  );
}

FilterBarButton.displayName = 'FilterBar.Button';