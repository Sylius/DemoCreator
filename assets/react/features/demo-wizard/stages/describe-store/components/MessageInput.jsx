import React, { useRef, useEffect } from 'react';

const MessageInput = ({ 
    input, 
    setInput, 
    handleSend, 
    loading, 
    disabled = false,
    messages = [],
    autoFocus = false,
    demoTypeText = "I’m selling to true audiophiles – offering hand-curated vinyl collections, premium-class turntables, tube amplifiers, and vintage audio sets. I’d like the vibe to be dark wood, leather armchairs, and the scent of premium nostalgia.",
    demoTypeDelay = 10,
    demoTypeRandom = false,
    autoTypeOnMount = true
}) => {
    const canSend = !!input.trim() && !loading && !disabled;
    const inputRef = useRef(null);
    const typingTimeoutsRef = useRef([]);
    const typingActiveRef = useRef(false);

    const resizeToContent = () => {
        const el = inputRef.current;
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = `${el.scrollHeight}px`;
    };

    // Show placeholder only when no messages yet
    const hasMessages = messages.length > 0;
    const placeholder = hasMessages
        ? "Type your message..."
        : "Type your message, e.g. \"I sell jewelry, sports accessories, etc.\"";

    // --- typing simulation helpers ---
    const clearTypingTimeouts = () => {
        typingTimeoutsRef.current.forEach(t => clearTimeout(t));
        typingTimeoutsRef.current = [];
    };

    const stopTyping = () => {
        typingActiveRef.current = false;
        clearTypingTimeouts();
    };

    /**
     * Simulate typing into the input.
     * @param {string} text - text to type
     * @param {{delay:number|[number,number], random?:boolean}} opts
     */
    const typeInInput = (text, opts = {}) => {
        const { delay = demoTypeDelay, random = demoTypeRandom } = opts;
        // Sanitize incoming text to avoid trailing 'undefined' artifacts
        let safeText = (text ?? "") + ""; // coerce to string
        // If someone concatenated an undefined, it often shows up at the end
        safeText = safeText.replace(/[\s\.,;:!\?\-–—]*undefined\s*$/i, "");
        stopTyping();
        typingActiveRef.current = true;
        setInput("");

        let i = 0;
        const step = () => {
            if (!typingActiveRef.current) return;
            if (i < safeText.length - 1) {
                setInput(prev => prev + safeText[i]);
                i += 1;
                // compute next delay
                let nextDelay = typeof delay === 'number' ? delay : (Math.floor(Math.random() * (delay[1] - delay[0] + 1)) + delay[0]);
                if (!Array.isArray(delay) && random) {
                    // jitter around the fixed delay (±40%)
                    const min = Math.max(10, Math.floor(delay * 0.6));
                    const max = Math.floor(delay * 1.4);
                    nextDelay = Math.floor(Math.random() * (max - min + 1)) + min;
                }
                const t = setTimeout(step, nextDelay);
                typingTimeoutsRef.current.push(t);
                inputRef.current?.focus();
            } else {
                stopTyping();
            }
        };
        step();
    };

    useEffect(() => {
        if (autoFocus && inputRef.current) {
            inputRef.current.focus();
            resizeToContent();
        }
    }, [autoFocus]);

    useEffect(() => {
        resizeToContent();
    }, [input]);

    // Auto-start typing demo when requested
    useEffect(() => {
        if (autoTypeOnMount && demoTypeText) {
            // If delay provided as a single number and random requested, we'll jitter per keystroke
            typeInInput(demoTypeText);
        }
        // Cleanup on unmount
        return () => {
            stopTyping();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [autoTypeOnMount, demoTypeText]);

    // Keep focus on input during typing
    const handleInputChange = (e) => {
        if (typingActiveRef.current) {
            stopTyping();
        }
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
                stopTyping();
                handleSend(e);
                // Refocus input after sending
                setTimeout(() => {
                    inputRef.current?.focus();
                }, 0);
            }
        }} className="relative flex items-center w-full p-0 bg-transparent" style={{marginTop: 0}}>
            <textarea
                ref={inputRef}
                rows={1}
                value={input}
                onChange={handleInputChange}
                onInput={resizeToContent}
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
                className="w-full pr-12 pl-4 py-3 rounded-2xl bg-gray-50 border-2 border-gray-200 text-base focus:outline-none focus:ring-0 focus:border-gray-300 transition-all shadow-sm"
                style={{ minHeight: 48, overflow: 'hidden', resize: 'none' }}
            ></textarea>
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
