import { render, screen } from '@testing-library/react';
import Cromo from '../../Components/ui/Cromo';

describe('Cromo', () => {
    it('renders children', () => {
        render(<Cromo><p>Contenido</p></Cromo>);
        expect(screen.getByText('Contenido')).toBeInTheDocument();
    });

    it('has pop-xl shadow class', () => {
        const { container } = render(<Cromo>X</Cromo>);
        expect(container.firstChild).toHaveClass('shadow-pop-xl');
    });

    it('does not render corner label when prop is absent', () => {
        render(<Cromo>X</Cromo>);
        expect(screen.queryByTestId('cromo-corner')).not.toBeInTheDocument();
    });

    it('renders corner label when prop is provided', () => {
        render(<Cromo corner="GRUPO A">X</Cromo>);
        expect(screen.getByTestId('cromo-corner')).toHaveTextContent('GRUPO A');
    });

    it('merges className', () => {
        const { container } = render(<Cromo className="p-4">X</Cromo>);
        expect(container.firstChild).toHaveClass('p-4');
    });
});
