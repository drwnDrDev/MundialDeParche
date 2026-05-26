import { render, screen } from '@testing-library/react';
import Button from '../../Components/ui/Button';

describe('Button', () => {
    it('renders children', () => {
        render(<Button>JUGAR</Button>);
        expect(screen.getByRole('button')).toHaveTextContent('JUGAR');
    });

    it('applies yel variant by default', () => {
        render(<Button>X</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-pop-yel');
    });

    it('applies red variant', () => {
        render(<Button variant="red">X</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-pop-red');
    });

    it('applies teal variant', () => {
        render(<Button variant="teal">X</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-pop-teal');
    });

    it('applies navy variant', () => {
        render(<Button variant="navy">X</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-navy');
    });

    it('applies ghost variant', () => {
        render(<Button variant="ghost">X</Button>);
        expect(screen.getByRole('button')).toHaveClass('bg-transparent');
    });

    it('applies lg size', () => {
        render(<Button size="lg">X</Button>);
        expect(screen.getByRole('button')).toHaveClass('text-lg');
    });

    it('applies full width', () => {
        render(<Button full>X</Button>);
        expect(screen.getByRole('button')).toHaveClass('w-full');
    });

    it('passes through native props', () => {
        render(<Button type="submit" disabled>X</Button>);
        const btn = screen.getByRole('button');
        expect(btn).toHaveAttribute('type', 'submit');
        expect(btn).toBeDisabled();
    });

    it('merges className', () => {
        render(<Button className="mt-4">X</Button>);
        expect(screen.getByRole('button')).toHaveClass('mt-4');
    });
});
