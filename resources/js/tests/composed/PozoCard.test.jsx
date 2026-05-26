import { render } from '@testing-library/react';
import PozoCard from '../../Components/composed/PozoCard';

const baseProps = {
    total: '4.200K',
    players: 84,
    amountPerPlayer: '50K',
    prize1: '2.940K',
    prize2: '1.260K',
};

describe('PozoCard', () => {
    it('renders total', () => {
        const { getByText } = render(<PozoCard {...baseProps} />);
        expect(getByText('4.200K')).toBeInTheDocument();
    });

    it('renders prize1', () => {
        const { getByText } = render(<PozoCard {...baseProps} />);
        expect(getByText('2.940K')).toBeInTheDocument();
    });

    it('renders prize2', () => {
        const { getByText } = render(<PozoCard {...baseProps} />);
        expect(getByText('1.260K')).toBeInTheDocument();
    });
});
