import { render } from '@testing-library/react';
import MatchCard from '../../Components/composed/MatchCard';

const baseProps = {
    teamA: 'COL', teamB: 'BRA',
    flagUrlA: 'https://flagcdn.com/w80/co.png',
    flagUrlB: 'https://flagcdn.com/w80/br.png',
    group: 'D', venue: 'MIAMI',
    time: '13:00',
};

describe('MatchCard', () => {
    it('renders teamA name', () => {
        const { getAllByText } = render(<MatchCard {...baseProps} status="upcoming" />);
        expect(getAllByText('COL')[0]).toBeInTheDocument();
    });

    it('live: muestra minuto', () => {
        const { getByText } = render(<MatchCard {...baseProps} status="live" scoreA={1} scoreB={0} minute="43'" />);
        expect(getByText("43'")).toBeInTheDocument();
    });

    it('ft: muestra FT', () => {
        const { getByText } = render(<MatchCard {...baseProps} status="ft" scoreA={2} scoreB={1} />);
        expect(getByText('FT')).toBeInTheDocument();
    });

    it('upcoming: muestra VS', () => {
        const { getByText } = render(<MatchCard {...baseProps} status="upcoming" />);
        expect(getByText('VS')).toBeInTheDocument();
    });

    it('con myPick: muestra pick en footer', () => {
        const { getByText } = render(<MatchCard {...baseProps} status="upcoming" myPick="2-1" />);
        expect(getByText(/TUS GOLES: 2-1/)).toBeInTheDocument();
    });

    it('sin myPick: muestra FALTAN TUS GOLES', () => {
        const { getByText } = render(<MatchCard {...baseProps} status="upcoming" />);
        expect(getByText(/FALTAN TUS GOLES/)).toBeInTheDocument();
    });
});
