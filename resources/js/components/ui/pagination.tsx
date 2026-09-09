import * as React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

interface PaginationProps extends React.HTMLAttributes<HTMLNavElement> {
  className?: string;
  pageCount: number;
  currentPage: number;
  boundaryCount?: number;
  siblingCount?: number;
  onPageChange?: (page: number) => void;
  nextLabel?: string;
  previousLabel?: string;
}

interface PaginationItemProps {
  className?: string;
  onClick?: () => void;
  children: React.ReactNode;
  'aria-current'?: string;
  'aria-disabled'?: boolean;
}

function getPageList({
  pageCount,
  currentPage,
  boundaryCount = 1,
  siblingCount = 1,
}: {
  pageCount: number;
  currentPage: number;
  boundaryCount?: number;
  siblingCount?: number;
}) {
  const leftSiblings = Math.max(0, currentPage - siblingCount - 1);
  const rightSiblings = Math.min(
    pageCount + 1,
    currentPage + siblingCount + 1
  );
  const showLeftDots = leftSiblings > boundaryCount;
  const showRightDots = rightSiblings < pageCount - boundaryCount;

  const pageList = [];

  if (showLeftDots) {
    for (let i = 0; i < boundaryCount; i++) {
      pageList.push({ type: 'page', page: i + 1 });
    }
    pageList.push({ type: 'dot' });
    for (
      let i = pageCount - boundaryCount - leftSiblings + 1;
      i < pageCount - boundaryCount + 1;
      i++
    ) {
      pageList.push({ type: 'page', page: i });
    }
  } else {
    for (let i = 0; i < leftSiblings; i++) {
      pageList.push({ type: 'page', page: i + 1 });
    }
  }

  for (
    let i = Math.max(boundaryCount + 1, leftSiblings + 1);
    i < Math.min(pageCount - boundaryCount, rightSiblings);
    i++
  ) {
    pageList.push({ type: 'page', page: i });
  }

  if (showRightDots) {
    for (
      let i = pageCount - boundaryCount + 1;
      i < pageCount - boundaryCount + leftSiblings + 1;
      i++
    ) {
      pageList.push({ type: 'page', page: i });
    }
    pageList.push({ type: 'dot' });
    for (
      let i = pageCount - boundaryCount + 1;
      i <= pageCount;
      i++
    ) {
      pageList.push({ type: 'page', page: i });
    }
  } else {
    for (
      let i = Math.max(boundaryCount + 1, rightSiblings);
      i <= pageCount;
      i++
    ) {
      pageList.push({ type: 'page', page: i });
    }
  }

  return pageList;
}

export function Pagination({
  className,
  pageCount,
  currentPage,
  boundaryCount = 1,
  siblingCount = 1,
  onPageChange,
  nextLabel = 'Next',
  previousLabel = 'Previous',
}: PaginationProps) {
  if (pageCount < 2) return null;

  const pageList = getPageList({ pageCount, currentPage, boundaryCount, siblingCount });

  return (
    <nav
      className={cn(
        'flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0',
        className
      )}
      aria-label="Pagination"
    >
      <div className="flex items-center justify-between">
        <button
          onClick={() => {
            if (currentPage > 1 && onPageChange) {
              onPageChange(currentPage - 1);
            }
          }}
          disabled={currentPage === 1}
          className={cn(
            'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/60 disabled:opacity-50 disabled:pointer-events-none',
            currentPage > 1
              ? 'text-foreground hover:bg-muted/50'
              : 'text-muted-foreground/50'
          )}
        >
          <ChevronLeft className="size-4" aria-hidden="true" />
          <span className="sr-only">{previousLabel}</span>
        </button>

        <div className="flex flex-wrap items-center gap-1">
          {pageList.map((item, index) => {
            if (item.type === 'page') {
              const isCurrent = item.page === currentPage;
              return (
                <button
                  key={item.page}
                  onClick={() => onPageChange?.(item.page)}
                  aria-current={isCurrent ? 'page' : undefined}
                  className={cn(
                    'flex h-9 w-9 items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/60',
                    isCurrent
                      ? 'bg-primary text-primary-foreground'
                      : 'text-muted-foreground/60 hover:bg-muted/50 hover:text-foreground'
                  )}
                >
                  {item.page}
                </button>
              );
            }
            return (
              <span key={index} className="flex h-9 w-9 items-center justify-center rounded-md text-sm font-medium">
                …
              </span>
            );
          })}
        </div>

        <button
          onClick={() => {
            if (currentPage < pageCount && onPageChange) {
              onPageChange(currentPage + 1);
            }
          }}
          disabled={currentPage === pageCount}
          className={cn(
            'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/60 disabled:opacity-50 disabled:pointer-events-none',
            currentPage < pageCount
              ? 'text-foreground hover:bg-muted/50'
              : 'text-muted-foreground/50'
          )}
        >
          <span className="sr-only">{nextLabel}</span>
          <ChevronRight className="size-4" aria-hidden="true" />
        </button>
      </div>
    </nav>
  );
}

Pagination.displayName = 'Pagination';