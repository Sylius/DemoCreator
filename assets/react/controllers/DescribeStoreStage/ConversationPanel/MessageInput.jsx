import React, { useRef, useEffect } from 'react';

const MessageInput = ({ 
    input, 
    setInput, 
    handleSend, 
    loading, 
    disabled = false,
    messages = [],
    autoFocus = false
}) => {
    const canSend = !!input.trim() && !loading && !disabled;
    const inputRef = useRef(null);
    
    // Show placeholder only when no messages yet
    const hasMessages = messages.length > 0;
    const placeholder = hasMessages 
        ? "Type your message..." 
        : "Type your message, e.g. \"I sell jewelry, sports accessories, etc.\"";

    useEffect(() => {
        if (autoFocus && inputRef.current) {
            inputRef.current.focus();
        }
    }, [autoFocus]);

    // Keep focus on input during typing
    const handleInputChange = (e) => {
        setInput(e.target.value);
        // Ensure focus stays on input
        if (inputRef.current && document.activeElement !== inputRef.current) {
            inputRef.current.focus();
        }
    };

    return (
        <form onSubmit={e => {
            e.preventDefault();
            if (canSend) {
                handleSend(e);
                // Refocus input after sending
                setTimeout(() => {
                    inputRef.current?.focus();
                }, 0);
            }
        }} className="relative flex items-center w-full p-0 bg-transparent" style={{marginTop: 0}}>
            <input
                ref={inputRef}
                type="text"
                value={input}
                onChange={handleInputChange}
                onBlur={() => {
                    // Prevent losing focus during typing
                    setTimeout(() => {
                        if (inputRef.current && !loading && !disabled) {
                            inputRef.current.focus();
                        }
                    }, 0);
                }}
                disabled={loading || disabled}
                placeholder={loading ? "Waiting..." : placeholder}
                className="w-full pr-12 pl-4 py-3 rounded-2xl bg-gray-50 border-2 border-gray-200 text-base focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all shadow-sm"
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