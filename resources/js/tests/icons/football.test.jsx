import { render } from '@testing-library/react';
import FlagSmall from '../../Components/icons/football/FlagSmall';
import Trophy from '../../Components/icons/football/Trophy';
import SoccerBall from '../../Components/icons/football/SoccerBall';
import Jersey from '../../Components/icons/football/Jersey';
import Boot from '../../Components/icons/football/Boot';
import Whistle from '../../Components/icons/football/Whistle';
import Stadium from '../../Components/icons/football/Stadium';
import GoalNet from '../../Components/icons/football/GoalNet';

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

    describe('Boot', () => {
        it('renders svg', () => {
            const { container } = render(<Boot />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });

    describe('Whistle', () => {
        it('renders svg', () => {
            const { container } = render(<Whistle />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });

    describe('Stadium', () => {
        it('renders svg', () => {
            const { container } = render(<Stadium />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });
    });

    describe('GoalNet', () => {
        it('renders svg', () => {
            const { container } = render(<GoalNet />);
            expect(container.querySelector('svg')).toBeInTheDocument();
        });

        it('renders 18 net lines (11 vertical + 7 horizontal)', () => {
            const { container } = render(<GoalNet />);
            const lines = container.querySelectorAll('line');
            expect(lines).toHaveLength(18);
        });
    });
});
