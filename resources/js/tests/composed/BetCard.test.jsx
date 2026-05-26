import { render } from '@testing-library/react';
import BetCard from '../../Components/composed/BetCard';

const baseProps = {
    teamA: 'ARG', teamB: 'ALE',
    flagUrlA: 'https://flagcdn.com/w80/ar.png',
    flagUrlB: 'https://flagcdn.com/w80/de.png',
    pick: '2-1', pts: '+10', time: 'EN 2H',
};

describe('BetCard', () => {
    it('renders pick', () => {
        const { getByText } = render(<BetCard {...baseProps} />);
        expect(getByText('2-1')).toBeInTheDocument();
    });

    it('renders team names', () => {
        const { getByText } = render(<BetCard {...baseProps} />);
        expect(getByText('ARG vs ALE')).toBeInTheDocument();
    });

    it('shows corner "¡EN VIVO!" when hot=true', () => {
        const { getByText } = render(<BetCard {...baseProps} hot />);
        expect(getByText('¡EN VIVO!')).toBeInTheDocument();
    });

    it('does not show corner when hot=false', () => {
        const { queryByText } = render(<BetCard {...baseProps} />);
        expect(queryByText('¡EN VIVO!')).not.toBeInTheDocument();
    });
});
