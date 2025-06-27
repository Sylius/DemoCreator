import React from 'react';
import { BrowserRouter, Routes, Route, Link, useLocation } from 'react-router-dom';
import DemoWizard from './controllers/DemoWizard';
import GptChatWindow from './controllers/GptChatWindow';

function Layout({ children }) {
    const location = useLocation();
    return (
        <div className="flex flex-col min-h-screen bg-gradient-to-br from-gray-50 to-white font-sans text-gray-900">
            {/*<header className="sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-gray-200 shadow-sm">*/}
            {/*    <div className="max-w-5xl mx-auto flex items-center justify-between px-6 py-3">*/}
            {/*        <Link to="/" className="flex items-center gap-2 font-bold text-2xl tracking-tight text-gray-900 no-underline">*/}
            {/*            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">*/}
            {/*                <rect width="32" height="32" rx="8" fill="#14B8A6"/>*/}
            {/*                <path d="M10 22L22 10M10 10h12v12" stroke="#fff" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>*/}
            {/*            </svg>*/}
            {/*            Demo Creator*/}
            {/*        </Link>*/}
            {/*        <nav className="flex gap-4 text-base">*/}
            {/*            <NavLink to="/wizard" active={location.pathname.startsWith('/wizard')}>Creator</NavLink>*/}
            {/*            <NavLink to="/chat" active={location.pathname.startsWith('/chat')}>Chat</NavLink>*/}
            {/*        </nav>*/}
            {/*    </div>*/}
            {/*</header>*/}
            <main className="flex-1 flex flex-col items-center px-4 py-8">
                <div className="w-full max-w-4xl">{children}</div>
            </main>
            {/*<footer className="border-t border-gray-200 text-center text-sm text-gray-500 py-6 bg-white/70">*/}
            {/*    &copy; {new Date().getFullYear()} Sylius Demo Creator. All rights reserved.*/}
            {/*</footer>*/}
        </div>
    );
}

function NavLink({ to, active, children }) {
    return (
        <Link
            to={to}
            className={
                `px-4 py-2 rounded-xl transition-colors duration-150 font-semibold no-underline ` +
                (active
                    ? 'bg-teal-100 text-teal-800 shadow'
                    : 'text-teal-600 hover:bg-teal-50 hover:text-teal-900 hover:underline')
            }
        >
            {children}
        </Link>
    );
}

function HomeHero() {
    return (
        <section className="flex flex-col items-center justify-center py-24 gap-6 text-center">
            <h1 className="text-2xl md:text-5xl font-extrabold tracking-tight mb-2 bg-gradient-to-r from-teal-600 to-cyan-500 bg-clip-text">
                Sylius Demo Creator
            </h1>
            <p className="text-lg md:text-xl text-gray-600 max-w-2xl mb-6">
                Craft your Sylius store in seconds with our intuitive demo creator.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <Link to="/wizard" className="px-6 py-3 rounded-xl bg-teal-600 text-white font-semibold shadow hover:bg-teal-700 transition text-lg no-underline hover:underline">
                    Begin!
                </Link>
                {/*<Link to="/chat" className="px-6 py-3 rounded-xl bg-white border border-gray-200 text-teal-700 font-semibold shadow hover:bg-teal-50 hover:text-teal-900 transition text-lg no-underline hover:underline">*/}
                {/*    Go to chat*/}
                {/*</Link>*/}
            </div>
        </section>
    );
}

export default function App() {
    return (
        <BrowserRouter>
            <Layout>
                <Routes>
                    <Route path="/wizard/:step?" element={<DemoWizard />} />
                    <Route path="/chat" element={<GptChatWindow />} />
                    <Route path="/" element={<HomeHero />} />
                </Routes>
            </Layout>
        </BrowserRouter>
    );
}
