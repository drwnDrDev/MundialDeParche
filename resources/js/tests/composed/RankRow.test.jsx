import { render } from '@testing-library/react';
import RankRow from '../../Components/composed/RankRow';

describe('RankRow', () => {
    it('renders name', () => {
        const { getByText } = render(
            <RankRow position={4} name="LUCHO M." pts="31" delta="+3" />
        );
        expect(getByText('LUCHO M.')).toBeInTheDocument();
    });

    it('renders pts', () => {
        const { getByText } = render(
            <RankRow position={4} name="LUCHO M." pts="31" delta="+3" />
        );
        expect(getByText('31')).toBeInTheDocument();
    });

    it('isYou=true aplica bg-pop-yel', () => {
        const { container } = render(
            <RankRow position={12} name="JHON M." pts="12" delta="+2" isYou />
        );
        expect(container.firstChild).toHaveClass('bg-pop-yel');
    });

    it('delta "+3" → chip tiene clase bg-pop-teal', () => {
        const { container } = render(
            <RankRow position={4} name="LUCHO M." pts="31" delta="+3" />
        );
        const chip = container.querySelector('.bg-pop-teal');
        expect(chip).toBeInTheDocument();
    });
});
