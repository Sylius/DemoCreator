import React, {useState} from 'react';
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
    const [showDebug, setShowDebug] = useState(false);
    // const isDev = typeof process !== 'undefined' && process.env.NODE_ENV !== 'production';
    const isDev = true; // For demonstration purposes, assume we're always in dev mode

    return (
        <div className="flex flex-col flex-1 min-h-0">
            {state === 'done' && (
                <div style={{display: "flex", alignItems: "center", marginBottom: 12}}>
                    <span style={{
                        width: 12,
                        height: 12,
                        borderRadius: "50%",
                        background: "#28a745",
                        display: "inline-block",
                        marginRight: 8
                    }}/>
                    <strong style={{color: "#28a745"}}>Wszystkie dane zebrane!</strong>
                </div>
            )}
            <MessageList
                messages={messages}
                showFunctionMessages={showFunctionMessages}
                height={undefined}
            />
            {error && (
                <div style={{color: "#dc3545", margin: "12px 0"}}>
                    <strong>Błąd:</strong> {error}
                </div>
            )}
            <div className="px-4 pb-4 pt-2 bg-white sticky bottom-0 z-10 rounded-3xl">
                <MessageInput
                    input={input}
                    setInput={setInput}
                    handleSend={handleSend}
                    loading={loading}
                    disabled={state === 'done'}
                    autoFocus={true}
                />
            </div>
            {isDev && (
                <div className="px-4 pb-4 pt-2 bg-white sticky bottom-0 z-10">
                    <button
                        onClick={() => setShowDebug(d => !d)}
                        style={{
                            position: 'absolute',
                            top: 12,
                            right: 24,
                            zIndex: 10,
                            background: showDebug ? '#10b981' : '#eee',
                            color: showDebug ? '#fff' : '#333',
                            border: 'none',
                            borderRadius: 6,
                            padding: '4px 12px',
                            fontSize: 13,
                            fontWeight: 600,
                            cursor: 'pointer',
                            boxShadow: '0 1px 4px #0001'
                        }}
                    >
                        {showDebug ? 'Hide debug' : 'Show debug'}
                    </button>
                </div>
            )}
            {showDebug && (
                <div>
                    <div style={{
                        background: '#f3f3f3',
                        fontSize: 13,
                        color: '#333',
                        wordBreak: 'break-all',
                    }}>
                        <div><b>input:</b> {JSON.stringify(input)}</div>
                        <div><b>loading:</b> {String(loading)}</div>
                        <div><b>state:</b> {state}</div>
                        <div><b>messages.length:</b> {messages.length}</div>
                        <div><b>error:</b> {error ? String(error) : 'null'}</div>
                    </div>
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
                </div>
            )}
        </div>
    );
};

export default ConversationPanel; 