import { render } from '@testing-library/react';
import TabBar from '../../Components/composed/TabBar';

describe('TabBar', () => {
    it('renders 4 tab buttons', () => {
        const { container } = render(<TabBar />);
        expect(container.querySelectorAll('button')).toHaveLength(4);
    });

    it('active tab has aria-current="page"', () => {
        const { container } = render(<TabBar active="matches" />);
        const buttons = container.querySelectorAll('button');
        const activeBtn = Array.from(buttons).find(b => b.getAttribute('aria-current') === 'page');
        expect(activeBtn).toHaveAttribute('aria-label', 'PARTIDOS');
    });

    it('inactive tabs do not have aria-current', () => {
        const { container } = render(<TabBar active="home" />);
        const buttons = container.querySelectorAll('button');
        const inactiveBtns = Array.from(buttons).filter(b => b.getAttribute('aria-label') !== 'PARCHE');
        inactiveBtns.forEach(b => expect(b).not.toHaveAttribute('aria-current'));
    });
});
