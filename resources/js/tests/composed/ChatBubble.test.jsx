import { render } from '@testing-library/react';
import ChatBubble from '../../Components/composed/ChatBubble';

const baseProps = {
    name: 'LUCHO',
    color: 'var(--c-teal)',
    text: '¡Eh ave maría!',
    time: '18:42',
};

describe('ChatBubble', () => {
    it('renders text', () => {
        const { getByText } = render(<ChatBubble {...baseProps} />);
        expect(getByText('¡Eh ave maría!')).toBeInTheDocument();
    });

    it('isMe=false → avatar visible con inicial', () => {
        const { getByText } = render(<ChatBubble {...baseProps} isMe={false} />);
        expect(getByText('L')).toBeInTheDocument();
    });

    it('isMe=true → no hay avatar con inicial del nombre', () => {
        const { queryByText } = render(<ChatBubble {...baseProps} isMe />);
        // No debe haber elemento con solo la inicial visible como avatar
        const avatarEl = queryByText('L');
        expect(avatarEl).not.toBeInTheDocument();
    });

    it('pinned=true → badge FIJO visible', () => {
        const { getByText } = render(<ChatBubble {...baseProps} pinned />);
        expect(getByText('FIJO')).toBeInTheDocument();
    });

    it('sticker → Burst visible con texto del sticker', () => {
        const { getByText } = render(<ChatBubble {...baseProps} sticker="BERRACO!" />);
        expect(getByText('BERRACO!')).toBeInTheDocument();
    });
});
