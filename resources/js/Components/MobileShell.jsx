export default function MobileShell({ children }) {
    return (
        <div className="bg-cream min-h-[100dvh] overflow-x-hidden">
            <div className="max-w-3xl mx-auto w-full relative" style={{ paddingBottom: 'calc(7rem + env(safe-area-inset-bottom, 0px))' }}>
                {children}
                <div className="flex justify-center items-center py-3 opacity-40">
                    <a href="https://dinamycode.com" target="_blank" rel="noopener noreferrer">
                        <img src="/images/dc_logo.png" alt="DinámyCode" className="h-5 w-auto" />
                    </a>
                </div>
            </div>
        </div>
    );
}
