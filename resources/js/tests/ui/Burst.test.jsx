import { render, screen } from '@testing-library/react';
import Burst from '../../Components/ui/Burst';

describe('Burst', () => {
    it('renders children', () => {
        render(<Burst>¡GOOOL!</Burst>);
        expect(screen.getByText('¡GOOOL!')).toBeInTheDocument();
    });

    it('applies yel color by default', () => {
        const { container } = render(<Burst>X</Burst>);
        // inner layer carries the color class
        const inner = container.querySelector('[data-burst-inner]');
        expect(inner).toHaveClass('bg-pop-yel');
    });

    it('applies red color', () => {
        const { container } = render(<Burst color="red">X</Burst>);
        const inner = container.querySelector('[data-burst-inner]');
        expect(inner).toHaveClass('bg-pop-red');
    });

    it('applies teal color', () => {
        const { container } = render(<Burst color="teal">X</Burst>);
        const inner = container.querySelector('[data-burst-inner]');
        expect(inner).toHaveClass('bg-pop-teal');
    });

    it('applies md size by default', () => {
        const { container } = render(<Burst>X</Burst>);
        expect(container.firstChild).toHaveClass('w-20');
    });

    it('applies sm size', () => {
        const { container } = render(<Burst size="sm">X</Burst>);
        expect(container.firstChild).toHaveClass('w-12');
    });

    it('applies lg size', () => {
        const { container } = render(<Burst size="lg">X</Burst>);
        expect(container.firstChild).toHaveClass('w-28');
    });

    it('applies rotation via inline style', () => {
        const { container } = render(<Burst rotate={14}>X</Burst>);
        expect(container.firstChild).toHaveStyle({ transform: 'rotate(14deg)' });
    });

    it('merges className', () => {
        const { container } = render(<Burst className="absolute top-2">X</Burst>);
        expect(container.firstChild).toHaveClass('absolute');
    });

    it('applies sm text size on inner span', () => {
        const { container } = render(<Burst size="sm">X</Burst>);
        expect(container.querySelector('span')).toHaveClass('text-[10px]');
    });

    it('applies md text size on inner span by default', () => {
        const { container } = render(<Burst>X</Burst>);
        expect(container.querySelector('span')).toHaveClass('text-xs');
    });

    it('applies lg text size on inner span', () => {
        const { container } = render(<Burst size="lg">X</Burst>);
        expect(container.querySelector('span')).toHaveClass('text-sm');
    });
});
