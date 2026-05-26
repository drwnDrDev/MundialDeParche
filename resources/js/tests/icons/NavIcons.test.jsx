import { render } from '@testing-library/react';
import { NavStadium, NavVS, NavTrophy, NavFire } from '../../Components/icons/NavIcons';

const ALL_ICONS = [
    ['NavStadium', NavStadium],
    ['NavVS', NavVS],
    ['NavTrophy', NavTrophy],
    ['NavFire', NavFire],
];

describe('NavIcons', () => {
    it.each(ALL_ICONS)('%s renders inactive', (_, Icon) => {
        const { container } = render(<Icon />);
        expect(container.querySelector('svg')).toBeInTheDocument();
    });

    it.each(ALL_ICONS)('%s renders active', (_, Icon) => {
        const { container } = render(<Icon active />);
        expect(container.querySelector('svg')).toBeInTheDocument();
    });

    it('NavStadium uses yel stroke when active', () => {
        const { container } = render(<NavStadium active />);
        const ellipse = container.querySelector('ellipse');
        expect(ellipse).toHaveAttribute('stroke', 'var(--c-yel)');
    });
});
