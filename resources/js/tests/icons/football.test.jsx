import { render } from '@testing-library/react';
import FlagSmall from '../../Components/icons/football/FlagSmall';

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
});
