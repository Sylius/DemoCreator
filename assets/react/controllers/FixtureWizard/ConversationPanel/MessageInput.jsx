import React, { useRef, useEffect } from 'react';

const MessageInput = ({ 
    input, 
    setInput, 
    handleSend, 
    loading, 
    disabled = false,
    placeholder = "Wpisz wiadomość, np. „Sprzedaję biżuterię, akcesoria sportowe itp.",
    autoFocus = false
}) => {
    const canSend = !!input.trim() && !loading && !disabled;
    const inputRef = useRef(null);

    useEffect(() => {
        if (autoFocus && inputRef.current) {
            inputRef.current.focus();
        }
    }, [autoFocus]);

    return (
        <form onSubmit={handleSend} className="relative flex items-center mt-3">
            {/* Arrow button inside input, left side */}
            <button
                type="submit"
                disabled={!canSend}
                className={`absolute left-2 top-1/2 -translate-y-1/2 flex items-center justify-center rounded-full w-8 h-8 transition-colors duration-150
                    ${canSend ? 'bg-teal-600 hover:bg-teal-700 text-white shadow' : 'bg-gray-200 text-gray-400 cursor-not-allowed'}`}
                tabIndex={-1} // so Enter in input submits, not button
                style={{ pointerEvents: canSend ? 'auto' : 'none' }}
            >
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 15V5M10 5l-4 4M10 5l4 4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
            </button>
            <input
                ref={inputRef}
                type="text"
                value={input}
                onChange={e => setInput(e.target.value)}
                disabled={loading || disabled}
                placeholder={loading ? "Czekaj..." : placeholder}
                className="pl-12 pr-4 py-2 w-full rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-200 text-base transition"
                style={{ minHeight: 44 }}
            />
        </form>
    );
};

export default MessageInput; 