export default function MobileShell({ children }) {
    return (
        <div className="bg-cream min-h-screen overflow-x-hidden">
            <div className="max-w-3xl mx-auto w-full pb-28 relative">
                {children}
            </div>
        </div>
    );
}
