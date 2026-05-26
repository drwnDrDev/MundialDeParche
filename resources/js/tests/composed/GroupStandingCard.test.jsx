import { render } from '@testing-library/react';
import GroupStandingCard from '../../Components/composed/GroupStandingCard';

const teams = [
    { flagUrl: 'https://flagcdn.com/w80/mx.png', name: 'MÉXICO',    pj: 1, g: 1, e: 0, p: 0, gf: 2, gc: 1, pts: 3 },
    { flagUrl: 'https://flagcdn.com/w80/kr.png', name: 'COREA',     pj: 1, g: 0, e: 0, p: 1, gf: 1, gc: 2, pts: 0 },
    { flagUrl: 'https://flagcdn.com/w80/cr.png', name: 'C.RICA',    pj: 0, g: 0, e: 0, p: 0, gf: 0, gc: 0, pts: 0 },
    { flagUrl: 'https://flagcdn.com/w80/ma.png', name: 'MARRUECOS', pj: 0, g: 0, e: 0, p: 0, gf: 0, gc: 0, pts: 0 },
];

describe('GroupStandingCard', () => {
    it('renders group name', () => {
        const { getByText } = render(<GroupStandingCard group="A" played="1 / 6 JUGADOS" teams={teams} />);
        expect(getByText('GRUPO A')).toBeInTheDocument();
    });

    it('renders first team name', () => {
        const { getByText } = render(<GroupStandingCard group="A" played="1 / 6 JUGADOS" teams={teams} />);
        expect(getByText('MÉXICO')).toBeInTheDocument();
    });

    it('top 2 muestran flecha ↑', () => {
        const { getAllByText } = render(<GroupStandingCard group="A" played="1 / 6 JUGADOS" teams={teams} />);
        expect(getAllByText('↑')).toHaveLength(2);
    });

    it('equipo con live=true muestra chip LIVE', () => {
        const teamsWithLive = teams.map((t, i) => i === 0 ? { ...t, live: true } : t);
        const { getByText } = render(<GroupStandingCard group="A" played="1 / 6 JUGADOS" teams={teamsWithLive} />);
        expect(getByText('LIVE')).toBeInTheDocument();
    });
});
