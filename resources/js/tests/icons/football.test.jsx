import { render } from '@testing-library/react';
import FlagSmall from '../../Components/icons/football/FlagSmall';
import Trophy from '../../Components/icons/football/Trophy';
import SoccerBall from '../../Components/icons/football/SoccerBall';
import Jersey from '../../Components/icons/football/Jersey';

describe('Football icons', () => {
    describe('FlagSmall', () => {
        it('renders US flag svg', () => {
            const { container } = render(<FlagSmall code="us" />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });

        it('renders CA flag svg', () => {
            const { container } = render(<FlagSmall code="ca" />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });

        it('renders MX flag svg', () => {
            const { container } = render(<FlagSmall code="mx" />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });

        it('returns null for unknown code', () => {
            const { container } = render(<FlagSmall code="zz" />);
            expect(container.querySelector('svg')).not.toBeInTheDocument();
        });
    });

    describe('Trophy', () => {
        it('renders svg', () => {
            const { container } = render(<Trophy />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });

    describe('SoccerBall', () => {
        it('renders svg', () => {
            const { container } = render(<SoccerBall />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });

    describe('Jersey', () => {
        it('renders svg', () => {
            const { container } = render(<Jersey />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });
});
