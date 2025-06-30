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
        <form onSubmit={handleSend} className="relative flex items-center w-full p-0 bg-transparent" style={{marginTop: 0}}>
            <input
                ref={inputRef}
                type="text"
                value={input}
                onChange={e => setInput(e.target.value)}
                disabled={loading || disabled}
                placeholder={loading ? "Czekaj..." : placeholder}
                className="w-full pr-12 pl-4 py-3 rounded-2xl bg-gray-50 border-none text-base focus:outline-none focus:ring-0 transition shadow-none"
                style={{ minHeight: 48 }}
            />
            <button
                type="submit"
                disabled={!canSend}
                className={`absolute right-2 top-1/2 -translate-y-1/2 flex items-center justify-center rounded-full w-9 h-9 transition-colors duration-150
                    ${canSend ? 'bg-teal-600 hover:bg-teal-700 text-white shadow' : 'bg-gray-200 text-gray-400 cursor-not-allowed'}`}
                tabIndex={-1}
                style={{ pointerEvents: canSend ? 'auto' : 'none' }}
            >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 15V5M10 5l-4 4M10 5l4 4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
            </button>
        </form>
    );
};

export default MessageInput; 