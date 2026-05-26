import { render } from '@testing-library/react';
import PtsBadge from '../../Components/composed/PtsBadge';

describe('PtsBadge', () => {
    it('renders value', () => {
        const { getByText } = render(<PtsBadge value="124" rank="#12" />);
        expect(getByText('124')).toBeInTheDocument();
    });

    it('renders rank', () => {
        const { getByText } = render(<PtsBadge value="124" rank="#12" />);
        expect(getByText('· #12')).toBeInTheDocument();
    });
});
