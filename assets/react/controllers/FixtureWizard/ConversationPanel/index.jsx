import React, { useState } from 'react';
import ConversationControls from './ConversationControls';
import MessageList from './MessageList';
import MessageInput from './MessageInput';

const ConversationPanel = ({
    messages,
    input,
    setInput,
    handleSend,
    loading,
    error,
    state,
    copyConversation,
    retryRequest,
    handleCreateFixtures,
    clearConversation
}) => {
    const [showFunctionMessages, setShowFunctionMessages] = useState(false);

    return (
        <div style={{
            flex: 1,
            border: "1px solid #ccc",
            borderRadius: 8,
            padding: 16,
            background: "#fff",
            boxShadow: "0 2px 8px #0001"
        }}>
            <ConversationControls
                copyConversation={copyConversation}
                retryRequest={retryRequest}
                showFunctionMessages={showFunctionMessages}
                setShowFunctionMessages={setShowFunctionMessages}
                handleCreateFixtures={handleCreateFixtures}
                clearConversation={clearConversation}
                loading={loading}
                state={state}
            />
            
            {state === 'done' && (
                <div style={{ display: "flex", alignItems: "center", marginBottom: 12 }}>
                    <span style={{
                        width: 12,
                        height: 12,
                        borderRadius: "50%",
                        background: "#28a745",
                        display: "inline-block",
                        marginRight: 8
                    }} />
                    <strong style={{ color: "#28a745" }}>Wszystkie dane zebrane!</strong>
                </div>
            )}
            
            <MessageList 
                messages={messages}
                showFunctionMessages={showFunctionMessages}
            />
            
            {error && (
                <div style={{ color: "#dc3545", margin: "12px 0" }}>
                    <strong>Błąd:</strong> {error}
                </div>
            )}
            
            <MessageInput
                input={input}
                setInput={setInput}
                handleSend={handleSend}
                loading={loading}
                disabled={state === 'done'}
                autoFocus={true}
            />
        </div>
    );
};

export default ConversationPanel; 