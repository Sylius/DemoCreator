import React from 'react';

const MessageInput = ({ 
    input, 
    setInput, 
    handleSend, 
    loading, 
    disabled = false,
    placeholder = "Wpisz wiadomość, np. „Sprzedaję biżuterię, akcesoria sportowe itp." 
}) => {
    return (
        <form onSubmit={handleSend} style={{ display: "flex", gap: 8, marginTop: 12 }}>
            <input
                type="text"
                value={input}
                onChange={e => setInput(e.target.value)}
                disabled={loading || disabled}
                placeholder={loading ? "Czekaj..." : placeholder}
                style={{ 
                    flex: 1, 
                    padding: 8, 
                    borderRadius: 4, 
                    border: "1px solid #ccc" 
                }}
            />
            <button 
                type="submit" 
                disabled={loading || !input.trim() || disabled} 
                style={{ 
                    padding: "8px 16px",
                    borderRadius: 4,
                    border: "1px solid #ccc",
                    background: "#fff",
                    cursor: loading || !input.trim() || disabled ? "not-allowed" : "pointer",
                    opacity: loading || !input.trim() || disabled ? 0.6 : 1
                }}
            >
                Wyślij
            </button>
        </form>
    );
};

export default MessageInput; 