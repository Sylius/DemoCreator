import React from 'react';

const ConversationControls = ({
    copyConversation,
    retryRequest,
    showFunctionMessages,
    setShowFunctionMessages,
    handleCreateFixtures,
    clearConversation,
    loading,
    state
}) => {
    return (
        <div style={{ display: "flex", justifyContent: "flex-end", marginBottom: 12 }}>
            <button
                onClick={copyConversation}
                title="Copy conversation as JSON"
                style={{
                    border: "none",
                    background: "transparent",
                    cursor: "pointer",
                    fontSize: "1rem",
                    color: "#007bff"
                }}
            >
                📋 Copy JSON
            </button>
            
            <button
                onClick={retryRequest}
                title="Retry last request"
                style={{
                    border: "none",
                    background: "transparent",
                    cursor: "pointer",
                    fontSize: "1rem",
                    color: "#ffc107",
                    marginLeft: 12
                }}
            >
                🔁 Retry
            </button>
            
            <label style={{ display: "flex", alignItems: "center", marginLeft: 12 }}>
                <input
                    type="checkbox"
                    checked={showFunctionMessages}
                    onChange={e => setShowFunctionMessages(e.target.checked)}
                    style={{ marginRight: 4 }}
                />
                Show functions
            </label>
            
            {state === 'ready' && (
                <button
                    onClick={handleCreateFixtures}
                    disabled={loading}
                    title="Create Fixtures"
                    style={{
                        border: "none",
                        background: "transparent",
                        cursor: loading ? "not-allowed" : "pointer",
                        fontSize: "1rem",
                        color: "#007bff",
                        marginLeft: 12,
                        opacity: loading ? 0.6 : 0.3
                    }}
                >
                    Create Fixtures
                </button>
            )}
            
            <button
                onClick={clearConversation}
                title="Clear conversation"
                style={{
                    border: "none",
                    background: "transparent",
                    cursor: "pointer",
                    fontSize: "1rem",
                    color: "#dc3545",
                    marginLeft: 12
                }}
            >
                🗑️ Clear
            </button>
        </div>
    );
};

export default ConversationControls; 