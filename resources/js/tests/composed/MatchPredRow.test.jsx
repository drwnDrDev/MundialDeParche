import { render } from '@testing-library/react';
import MatchPredRow from '../../Components/composed/MatchPredRow';

const baseProps = {
    date: '11 JUN · 19:00', venue: 'AZTECA',
    teamHome: 'MEX', teamAway: 'KOR',
    flagUrlHome: 'https://flagcdn.com/w80/mx.png',
    flagUrlAway: 'https://flagcdn.com/w80/kr.png',
    scoreHome: 2, scoreAway: 1,
    status: 'ok',
};

describe('MatchPredRow', () => {
    it('renders teamHome name', () => {
        const { getByText } = render(<MatchPredRow {...baseProps} />);
        expect(getByText('MEX')).toBeInTheDocument();
    });

    it('status ok → muestra GUARDADO', () => {
        const { getByText } = render(<MatchPredRow {...baseProps} status="ok" />);
        expect(getByText(/GUARDADO/)).toBeInTheDocument();
    });

    it('status empty → muestra FALTAN TUS GOLES', () => {
        const { getByText } = render(
            <MatchPredRow {...baseProps} status="empty" scoreHome={null} scoreAway={null} />
        );
        expect(getByText(/FALTAN TUS GOLES/)).toBeInTheDocument();
    });

    it('ScoreBox filled cuando status=ok', () => {
        const { container } = render(<MatchPredRow {...baseProps} status="ok" />);
        const scoreBoxes = container.querySelectorAll('.bg-pop-yel');
        expect(scoreBoxes.length).toBeGreaterThan(0);
    });
});
