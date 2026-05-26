import { render, screen } from '@testing-library/react';
import Halftone from '../../Components/ui/Halftone';

describe('Halftone', () => {
    it('renders children', () => {
        render(<Halftone><p>Contenido</p></Halftone>);
        expect(screen.getByText('Contenido')).toBeInTheDocument();
    });

    it('wrapper is relative', () => {
        const { container } = render(<Halftone>X</Halftone>);
        expect(container.firstChild).toHaveClass('relative');
    });

    it('overlay has pointer-events-none', () => {
        const { container } = render(<Halftone>X</Halftone>);
        const overlay = container.querySelector('[data-halftone-overlay]');
        expect(overlay).toHaveClass('pointer-events-none');
    });

    it('applies ink texture by default', () => {
        const { container } = render(<Halftone>X</Halftone>);
        const overlay = container.querySelector('[data-halftone-overlay]');
        expect(overlay).toHaveClass('halftone');
    });

    it('applies red texture', () => {
        const { container } = render(<Halftone color="red">X</Halftone>);
        const overlay = container.querySelector('[data-halftone-overlay]');
        expect(overlay).toHaveClass('halftone-red');
    });

    it('applies yel texture', () => {
        const { container } = render(<Halftone color="yel">X</Halftone>);
        const overlay = container.querySelector('[data-halftone-overlay]');
        expect(overlay).toHaveClass('halftone-yel');
    });

    it('applies teal texture', () => {
        const { container } = render(<Halftone color="teal">X</Halftone>);
        const overlay = container.querySelector('[data-halftone-overlay]');
        expect(overlay).toHaveClass('halftone-teal');
    });

    it('applies navy texture', () => {
        const { container } = render(<Halftone color="navy">X</Halftone>);
        const overlay = container.querySelector('[data-halftone-overlay]');
        expect(overlay).toHaveClass('halftone-navy');
    });

    it('merges className on wrapper', () => {
        const { container } = render(<Halftone className="p-6">X</Halftone>);
        expect(container.firstChild).toHaveClass('p-6');
    });
});
