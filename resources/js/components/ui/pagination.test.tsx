import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { Pagination } from './pagination';

describe('pagination boundaries', () => {
    it.each([1, 2, 5, 9, 10])('offers valid unique pages including current page %i and both ends', (currentPage) => {
        const go = vi.fn();
        render(<Pagination pageCount={10} currentPage={currentPage} onPageChange={go} />);
        const pages = screen.getAllByRole('button').map((button) => Number(button.textContent)).filter((page) => !Number.isNaN(page));
        expect(pages.length).toBe(new Set(pages).size);
        expect(pages.every((page) => page >= 1 && page <= 10)).toBe(true);
        expect(pages).toEqual([...pages].sort((a, b) => a - b));
        expect(pages).toContain(1);
        expect(pages).toContain(10);
        expect(screen.getByRole('button', { name: String(currentPage) }).getAttribute('aria-current')).toBe('page');
        fireEvent.click(screen.getByRole('button', { name: '10' }));
        expect(go).toHaveBeenCalledWith(10);
    });

    it('renders both numbered pages when there are only two', () => {
        render(<Pagination pageCount={2} currentPage={1} />);
        expect(screen.getByRole('button', { name: '1' })).not.toBeNull();
        expect(screen.getByRole('button', { name: '2' })).not.toBeNull();
    });
});
