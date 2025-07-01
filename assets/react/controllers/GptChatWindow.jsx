import React, {useState, useRef, useEffect} from "react";
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import rehypeRaw from 'rehype-raw';
import { Prism as SyntaxHighlighter } from 'react-syntax-highlighter';
import { tomorrow } from 'react-syntax-highlighter/dist/esm/styles/prism';
import StoreDetailsSummary from "./DescribeStoreStage/StoreDetailsPanel/StoreDetailsSummary";

const GptChatWindow = ({onNext}) => {
    const [messages, setMessages] = useState(() => {
        const stored = localStorage.getItem('messages');
        return stored ? JSON.parse(stored) : [];
    });
    const [input, setInput] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [state, setState] = useState(() => {
        return localStorage.getItem('state') || 'collecting';
    });
    const [storeDetails, setStoreDetails] = useState(() => {
        const stored = localStorage.getItem('storeDetails');
        return stored ? JSON.parse(stored) : null;
    });
    const [showFunctionMessages, setShowFunctionMessages] = useState(false);
    const [conversationId, setConversationId] = useState(() => {
        return localStorage.getItem('conversationId') || null;
    });
    const chatEndRef = useRef(null);

    useEffect(() => {
        chatEndRef.current?.scrollIntoView({behavior: "smooth"});
    }, [messages, loading]);

    useEffect(() => {
        if (conversationId) {
            localStorage.setItem('conversationId', conversationId);
        }
    }, [conversationId]);

    useEffect(() => {
        localStorage.setItem('messages', JSON.stringify(messages));
    }, [messages]);

    useEffect(() => {
        if (storeDetails !== null) {
            localStorage.setItem('storeDetails', JSON.stringify(storeDetails));
        } else {
            localStorage.removeItem('storeDetails');
        }
    }, [storeDetails]);

    useEffect(() => {
        if (state) {
            localStorage.setItem('state', state);
        }
    }, [state]);

    // Automatyczne wysyłanie kolejnego requestu, jeśli ostatnia wiadomość to function_call
    useEffect(() => {
        if (
            messages.length > 0 &&
            messages[messages.length - 1].function_call &&
            !loading
        ) {
            // Wyślij automatycznie kolejny request
            handleSend({ preventDefault: () => {} }, true);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [messages]);

    const handleSend = async (e, auto = false) => {
        if (!auto) e.preventDefault();
        if (!auto && !input.trim()) return;
        setError(null);
        const newMessages = auto
            ? [...messages]
            : [...messages, { role: "user", content: input }];
        if (!auto) setMessages(newMessages);
        if (!auto) setInput("");
        setLoading(true);
        try {
            const payload = {
                conversationId,
                messages: newMessages,
                storeDetails,
                state,
                error,
            };
            const response = await fetch("/api/chat", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });
            const rawResponse = await response.text();
            let data;
            try {
                data = JSON.parse(rawResponse);
            } catch (parseError) {
                setError(`Invalid JSON response from API:\n${rawResponse}`);
                setState('error');
                setLoading(false);
                return;
            }
            if (data.error) setError(data.error); else setError(null);
            if (data.state) setState(data.state);
            if (data.conversationId) setConversationId(data.conversationId);
            if (data.storeDetails) setStoreDetails(data.storeDetails);
            if (Array.isArray(data.messages)) {
                setMessages(data.messages);
            } else {
                setError("Missing 'messages' in API response");
            }
        } catch (err) {
            setError(err.message || "Unknown error");
            setState('error');
        } finally {
            setLoading(false);
        }
    };

    const copyConversation = () => {
        const payload = JSON.stringify(
            { conversationId, messages, storeDetails, state, error },
            null,
            2
        );
        navigator.clipboard.writeText(payload)
            .then(() => alert("Conversation copied to clipboard!"))
            .catch(() => alert("Failed to copy conversation."));
    };

    const retryRequest = async () => {
        setLoading(true);
        try {
            const payload = {
                conversationId,
                messages,
                storeDetails,
                state,
                error,
            };
            const response = await fetch("/api/chat", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });
            const rawResponse = await response.text();
            let data;
            try {
                data = JSON.parse(rawResponse);
            } catch (parseError) {
                setError(`Invalid JSON response from API:\n${rawResponse}`);
                setState('error');
                setLoading(false);
                return;
            }
            if (data.error) setError(data.error); else setError(null);
            if (data.state) setState(data.state);
            if (data.conversationId) setConversationId(data.conversationId);
            if (data.storeDetails) setStoreDetails(data.storeDetails);
            if (Array.isArray(data.messages)) {
                setMessages(data.messages);
            } else {
                setError("Missing 'messages' in API response");
            }
        } catch (err) {
            setError(err.message || "Unknown error");
            setState('error');
        } finally {
            setLoading(false);
        }
    };

    const handleCreateFixtures = async (overrideStoreDetails) => {
        const details = overrideStoreDetails !== undefined ? overrideStoreDetails : storeDetails;
        setError(null);
        setLoading(true);
        const payload = { conversationId, messages, storeDetails: details, state, error };
        try {
            const response = await fetch("/api/create-fixtures", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });
            const rawResponse = await response.text();
            let data;
            try {
                data = JSON.parse(rawResponse);
            } catch (parseError) {
                setError(`Invalid JSON response from API:\n${rawResponse}`);
                setState('error');
                return;
            }
            if (data.error) setError(data.error); else setError(null);
            if (data.state) setState(data.state);
            if (data.conversationId) setConversationId(data.conversationId);
            if (data.storeDetails) setStoreDetails(data.storeDetails);
            if (Array.isArray(data.messages)) {
                setMessages(data.messages);
            } else {
                setError("Missing 'messages' in API response");
            }
        } catch (err) {
            setError(err.message || "Unknown error");
            setState('error');
        } finally {
            setLoading(false);
        }
    };

    const clearConversation = () => {
        setConversationId(null);
        localStorage.removeItem('conversationId');
        localStorage.removeItem('messages');
        localStorage.removeItem('storeDetails');
        localStorage.removeItem('state');
        setStoreDetails(null);
        setMessages([]);
        setInput("");
        setError(null);
        setLoading(false);
        setState('collecting');
    };

    return (
        <div style={{
            maxWidth: 1000,
            margin: "2rem auto",
            border: "1px solid #ccc",
            borderRadius: 8,
            padding: 16,
            background: "#fff",
            boxShadow: "0 2px 8px #0001"
        }}>
            <StoreDetailsSummary storeDetails={storeDetails} />
            <div style={{ display: "flex", justifyContent: "flex-end", marginBottom: 12 }}>
                <button
                    onClick={copyConversation}
                    title="Kopiuj konwersację w JSON"
                    style={{
                        border: "none",
                        background: "transparent",
                        cursor: "pointer",
                        fontSize: "1rem",
                        color: "#007bff"
                    }}
                >
                    📋 Kopiuj JSON
                </button>
                <button
                    onClick={retryRequest}
                    title="Wyślij ponownie ostatni request"
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
                    Pokaż funkcje
                </label>
                {state === 'awaiting_confirmation' && (
                    <button
                        onClick={handleCreateFixtures}
                        disabled={loading}
                        title="Create Fixtures"
                        style={{
                            border: "none",
                            background: "transparent",
                            cursor: "pointer",
                            fontSize: "1rem",
                            color: "#007bff",
                            marginLeft: 12
                        }}
                    >
                        Create Fixtures
                    </button>
                )}
                <button
                    onClick={clearConversation}
                    title="Wyczyść konwersację"
                    style={{
                        border: "none",
                        background: "transparent",
                        cursor: "pointer",
                        fontSize: "1rem",
                        color: "#dc3545",
                        marginLeft: 12
                    }}
                >
                    🗑️ Wyczyść
                </button>
            </div>
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
            <div style={{
                height: 650,
                overflowY: "auto",
                marginBottom: 12,
                background: "#f9f9f9",
                padding: 8,
                borderRadius: 4
            }}>
                {messages
                    .filter(m => m.role !== "system" && (showFunctionMessages || m.role !== "function"))
                    .map((msg, idx) => (
                        <div key={idx} style={{margin: "8px 0", textAlign: msg.role === "user" ? "right" : "left"}}>
                            <div style={{
                                display: "inline-block",
                                padding: "8px 12px",
                                borderRadius: 16,
                                background: msg.role === "user" ? "#d1e7dd" : "#e2e3e5",
                                color: "#222",
                                maxWidth: "80%"
                            }}>
                                {msg.role === "function" ? (
                                    <pre style={{ whiteSpace: "pre-wrap", margin: 0 }}>
                                        <code>{JSON.stringify(JSON.parse(msg.content), null, 2)}</code>
                                    </pre>
                                ) : (
                                    <ReactMarkdown
                                        remarkPlugins={[remarkGfm]}
                                        rehypePlugins={[rehypeRaw]}
                                        components={{
                                            code({node, inline, className, children, ...props}) {
                                                const match = /language-(\w+)/.exec(className || '');
                                                return !inline && match ? (
                                                    <SyntaxHighlighter style={tomorrow} language={match[1]} PreTag="div" {...props}>
                                                        {String(children).replace(/\n$/, '')}
                                                    </SyntaxHighlighter>
                                                ) : (
                                                    <code className={className} {...props}>{children}</code>
                                                );
                                            }
                                        }}
                                    >
                                        {msg.content}
                                    </ReactMarkdown>
                                )}
                            </div>
                        </div>
                    ))}
                <div ref={chatEndRef} />
            </div>
            {error && (
                <div style={{ color: "#dc3545", margin: "12px 0" }}>
                    <strong>Błąd:</strong> {error}
                </div>
            )}
            <form onSubmit={handleSend} style={{ display: "flex", gap: 8, marginTop: 12 }}>
                <input
                    type="text"
                    value={input}
                    onChange={e => setInput(e.target.value)}
                    disabled={loading || state === 'done'}
                    placeholder={loading ? "Czekaj..." : "Wpisz wiadomość, np. „Sprzedaję biżuterię, akcesoria sportowe itp."}
                    style={{ flex: 1, padding: 8, borderRadius: 4, border: "1px solid #ccc" }}
                />
                <button type="submit" disabled={loading || !input.trim() || state === 'done'} style={{ padding: "8px 16px" }}>
                    Wyślij
                </button>
            </form>
        </div>
    );
};

export default GptChatWindow;
    // Retry request function (if exists in your codebase, apply the same patch)
    // If you have a retryRequest function, replace the fetch/parse block with the following:
    /*
            const response = await fetch("/api/chat", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });
            const rawResponse = await response.text();
            let data;
            try {
                data = JSON.parse(rawResponse);
            } catch (parseError) {
                setError(`Nieprawidłowa odpowiedź JSON z API:\n${rawResponse}`);
                setState('error');
                setLoading(false);
                return;
            }
    */