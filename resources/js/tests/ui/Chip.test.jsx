import { render, screen } from '@testing-library/react';
import Chip from '../../Components/ui/Chip';

describe('Chip', () => {
    it('renders children', () => {
        render(<Chip>EN VIVO</Chip>);
        expect(screen.getByText('EN VIVO')).toBeInTheDocument();
    });

    it('applies white variant by default', () => {
        const { container } = render(<Chip>X</Chip>);
        expect(container.firstChild).toHaveClass('bg-white');
    });

    it('applies red variant', () => {
        const { container } = render(<Chip variant="red">X</Chip>);
        expect(container.firstChild).toHaveClass('bg-pop-red');
    });

    it('applies yel variant', () => {
        const { container } = render(<Chip variant="yel">X</Chip>);
        expect(container.firstChild).toHaveClass('bg-pop-yel');
    });

    it('applies teal variant', () => {
        const { container } = render(<Chip variant="teal">X</Chip>);
        expect(container.firstChild).toHaveClass('bg-pop-teal');
    });

    it('applies navy variant', () => {
        const { container } = render(<Chip variant="navy">X</Chip>);
        expect(container.firstChild).toHaveClass('bg-navy');
    });

    it('merges className', () => {
        const { container } = render(<Chip className="mt-2">X</Chip>);
        expect(container.firstChild).toHaveClass('mt-2');
    });
});
