import { render } from '@testing-library/react';
import StatCard from '../../Components/composed/StatCard';

describe('StatCard', () => {
    it('renders label', () => {
        const { getByText } = render(<StatCard label="POSICIÓN" value="#12" sub="/ 84" icon="trophy" />);
        expect(getByText('POSICIÓN')).toBeInTheDocument();
    });

    it('renders value', () => {
        const { getByText } = render(<StatCard label="POSICIÓN" value="#12" sub="/ 84" icon="trophy" />);
        expect(getByText('#12')).toBeInTheDocument();
    });

    it('renders svg icon', () => {
        const { container } = render(<StatCard label="EXACTOS" value="2" sub="+10 pts" icon="ball" />);
        expect(container.querySelector('svg')).toBeInTheDocument();
    });
});
