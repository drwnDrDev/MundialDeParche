const FLAGS = {
    us: (h) => (
        <svg width={h * 1.6} height={h} viewBox="0 0 16 10">
            <rect width="16" height="10" fill="#bf0a30" />
            <rect y="2" width="16" height="2" fill="#fff" />
            <rect y="6" width="16" height="2" fill="#fff" />
            <rect width="7" height="6" fill="#002868" />
        </svg>
    ),
    ca: (h) => (
        <svg width={h * 1.6} height={h} viewBox="0 0 16 10">
            <rect width="16" height="10" fill="#fff" />
            <rect width="5" height="10" fill="#d52b1e" />
            <rect x="11" width="5" height="10" fill="#d52b1e" />
            <path d="M8 3 L9 5 L11 5 L9.5 6.5 L10 8.5 L8 7.5 L6 8.5 L6.5 6.5 L5 5 L7 5 Z" fill="#d52b1e" />
        </svg>
    ),
    mx: (h) => (
        <svg width={h * 1.6} height={h} viewBox="0 0 16 10">
            <rect width="5.33" height="10" fill="#006847" />
            <rect x="5.33" width="5.34" height="10" fill="#fff" />
            <rect x="10.67" width="5.33" height="10" fill="#ce1126" />
        </svg>
    ),
};

export default function FlagSmall({ code, h = 10 }) {
    const flag = FLAGS[code];
    if (!flag) return null;
    return (
        <span className="border border-ink inline-flex leading-none">
            {flag(h)}
        </span>
    );
}
