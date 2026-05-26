import { render } from '@testing-library/react';
import PodiumStep from '../../Components/composed/PodiumStep';

const singleUser = [{ name: 'LUCHO M.', color: 'var(--c-yel)' }];
const tiedUsers  = [
    { name: 'LUCHO M.', color: 'var(--c-yel)' },
    { name: 'BRENDA',   color: 'var(--c-teal)' },
    { name: 'PEPE B.',  color: 'var(--c-red)' },
];
const manyUsers = [...tiedUsers,
    { name: 'EXTRA1', color: 'var(--c-cream)' },
    { name: 'EXTRA2', color: 'var(--c-navy)' },
];

describe('PodiumStep', () => {
    it('renders pts', () => {
        const { getByText } = render(<PodiumStep place={1} pts="48" tied={singleUser} />);
        expect(getByText('48 pts')).toBeInTheDocument();
    });

    it('place=1 con tied.length=3: muestra chip empate', () => {
        const { getByText } = render(<PodiumStep place={1} pts="48" tied={tiedUsers} />);
        expect(getByText('3 EMPATAN')).toBeInTheDocument();
    });

    it('place=1 con tied.length=1: no muestra chip empate', () => {
        const { queryByText } = render(<PodiumStep place={1} pts="48" tied={singleUser} />);
        expect(queryByText(/EMPATAN/)).not.toBeInTheDocument();
    });

    it('tied.length=5: muestra "+2" pill', () => {
        const { getByText } = render(<PodiumStep place={1} pts="48" tied={manyUsers} />);
        expect(getByText('+2')).toBeInTheDocument();
    });
});
