import { NavStadium, NavVS, NavTrophy, NavFire } from '@/Components/icons/NavIcons';

const TABS = [
    { id: 'home',    label: 'PARCHE',   Icon: NavStadium },
    { id: 'matches', label: 'PARTIDOS', Icon: NavVS },
    { id: 'rank',    label: 'RANKING',  Icon: NavTrophy },
    { id: 'chat',    label: 'CHAT',     Icon: NavFire },
];

export default function TabBar({ active = 'home' }) {
    return (
        <nav className="fixed bottom-0 left-0 right-0 bg-cream border-t-[3px] border-ink px-3 pt-2.5 pb-[22px] flex justify-between gap-1.5">
            {TABS.map((tab) => {
                const isActive = tab.id === active;
                return (
                    <button
                        key={tab.id}
                        aria-label={tab.label}
                        aria-current={isActive ? 'page' : undefined}
                        className={[
                            'flex-1 flex items-center justify-center py-2 px-1 border-[2.5px]',
                            isActive
                                ? 'bg-ink border-ink shadow-[3px_3px_0_var(--c-red)]'
                                : 'bg-transparent border-transparent',
                        ].join(' ')}
                    >
                        <tab.Icon active={isActive} />
                    </button>
                );
            })}
        </nav>
    );
}
