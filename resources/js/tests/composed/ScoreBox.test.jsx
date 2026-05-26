import { render } from '@testing-library/react';
import ScoreBox from '../../Components/composed/ScoreBox';

describe('ScoreBox', () => {
    it('renders value when provided', () => {
        const { getByText } = render(<ScoreBox value={2} filled />);
        expect(getByText('2')).toBeInTheDocument();
    });

    it('renders "—" when value is null', () => {
        const { getByText } = render(<ScoreBox value={null} />);
        expect(getByText('—')).toBeInTheDocument();
    });

    it('applies bg-pop-yel when filled', () => {
        const { container } = render(<ScoreBox value={1} filled />);
        expect(container.firstChild).toHaveClass('bg-pop-yel');
    });
});
