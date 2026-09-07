import { cn } from '@/lib/utils';

interface LoadingSkeletonProps {
    className?: string;
    variant?: 'text' | 'circular' | 'rectangular';
    width?: string | number;
    height?: string | number;
}

export function LoadingSkeleton({ className, variant = 'text', width, height }: LoadingSkeletonProps) {
    const baseClasses = 'animate-pulse bg-muted';
    const variantClasses = {
        text: 'h-4 rounded',
        circular: 'rounded-full',
        rectangular: 'rounded-md',
    };

    return (
        <div
            className={cn(baseClasses, variantClasses[variant], className)}
            style={{
                width: typeof width === 'number' ? `${width}px` : width,
                height: typeof height === 'number' ? `${height}px` : height,
            }}
        />
    );
}

export function TableSkeleton({ rows = 5, cols = 4 }: { rows?: number; cols?: number }) {
    return (
        <div className="w-full">
            <div className="flex items-center gap-4 border-b pb-4">
                {Array.from({ length: cols }).map((_, i) => (
                    <div key={i} className="h-4 flex-1 animate-pulse rounded bg-muted" />
                ))}
            </div>
            {Array.from({ length: rows }).map((_, rowIndex) => (
                <div key={rowIndex} className="flex items-center gap-4 border-b py-4">
                    {Array.from({ length: cols }).map((_, colIndex) => (
                        <div key={colIndex} className="h-4 flex-1 animate-pulse rounded bg-muted" />
                    ))}
                </div>
            ))}
        </div>
    );
}
