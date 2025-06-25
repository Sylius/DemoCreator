import React, {useState, useRef, useEffect} from "react";
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import rehypeRaw from 'rehype-raw';
import { Prism as SyntaxHighlighter } from 'react-syntax-highlighter';
import { tomorrow } from 'react-syntax-highlighter/dist/esm/styles/prism';

const GptChatWindow = ({onNext}) => {
    const [messages, setMessages] = useState(() => {
        const stored = localStorage.getItem('messages');
        return stored ? JSON.parse(stored) : [];
    });
    const [input, setInput] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [status, setStatus] = useState(() => {
        return localStorage.getItem('status') || 'collecting';
    });
    const [dataCompleted, setDataCompleted] = useState(false);
    const [storeConfiguration, setStoreConfiguration] = useState(() => {
        const stored = localStorage.getItem('storeConfiguration');
        return stored ? JSON.parse(stored) : null;
    });
    const [showFunctionMessages, setShowFunctionMessages] = useState(false);
    const [fixturesJson, setFixturesJson] = useState(null);
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
        if (storeConfiguration !== null) {
            localStorage.setItem('storeConfiguration', JSON.stringify(storeConfiguration));
        } else {
            localStorage.removeItem('storeConfiguration');
        }
    }, [storeConfiguration]);

    useEffect(() => {
        if (status) {
            localStorage.setItem('status', status);
        }
    }, [status]);

    const handleSend = async (e) => {
        e.preventDefault();
        if (!input.trim()) return;
        setError(null);
        const newMessages = [...messages, {role: "user", content: input}];
        setMessages(newMessages);
        setInput("");
        setLoading(true);
        try {
            const payload = {
                conversationId,
                messages: newMessages,
                storeConfiguration,
                fixtures: null,
                dataCompleted,
                status,
            };
            const response = await fetch("/api/chat", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (data.error) throw new Error(data.error);
            if (data.status) setStatus(data.status);
            if ('dataCompleted' in data) setDataCompleted(data.dataCompleted);
            if (data.conversationId) setConversationId(data.conversationId);
            if (data.storeConfiguration) setStoreConfiguration(data.storeConfiguration);
            if (Array.isArray(data.messages)) {
                setMessages(data.messages);
            } else {
                setError("Brak 'messages' w odpowiedzi API");
            }
            if (data.fixtures) {
                setFixturesJson(JSON.stringify(data.fixtures, null, 2));
            }
        } catch (err) {
            setError(err.message || "Unknown error");
            setStatus('error');
        } finally {
            setLoading(false);
        }
    };

    const copyConversation = () => {
        const payload = JSON.stringify(
            { conversationId, messages, storeConfiguration, dataCompleted, status },
            null,
            2
        );
        navigator.clipboard.writeText(payload)
            .then(() => alert("Rozmowa skopiowana do schowka!"))
            .catch(() => alert("Nie udało się skopiować rozmowy."));
    };
    const clearConversation = () => {
        setConversationId(null);
        localStorage.removeItem('conversationId');
        localStorage.removeItem('messages');
        localStorage.removeItem('storeConfiguration');
        localStorage.removeItem('status');
        setStoreConfiguration(null);
        setMessages([]);
        setInput("");
        setError(null);
        setLoading(false);
        setDataCompleted(false);
        setStatus('collecting');
        setFixturesJson(null);
    };

    const handleGenerate = async () => {
        setError(null);
        setLoading(true);
        try {
            const payload = {
                conversationId,
                messages,
                storeConfiguration,
                fixtures: null,
                dataCompleted,
                status: 'generating',
            };
            const response = await fetch("/api/chat", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (data.fixtures) {
                setFixturesJson(JSON.stringify(data.fixtures, null, 2));
                setStatus('done');
            } else if (data.error) {
                throw new Error(data.error);
            } else {
                throw new Error("Brak fixtures w odpowiedzi API");
            }
        } catch (err) {
            setError(err.message || "Unknown error generating fixtures");
            setStatus('error');
        } finally {
            setLoading(false);
        }
    };

    const copyFixtures = () => {
        if (fixturesJson) {
            navigator.clipboard.writeText(fixturesJson)
                .then(() => alert("Fixtures skopiowane do schowka!"))
                .catch(() => alert("Nie udało się skopiować fixtures."));
        }
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
                <label style={{ display: "flex", alignItems: "center", marginLeft: 12 }}>
                    <input
                        type="checkbox"
                        checked={showFunctionMessages}
                        onChange={e => setShowFunctionMessages(e.target.checked)}
                        style={{ marginRight: 4 }}
                    />
                    Pokaż funkcje
                </label>
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
            {status === 'done' && (
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
            {fixturesJson && (
                <div style={{ margin: "16px 0" }}>
                    <h4>Wygenerowane fixtures</h4>
                    <pre style={{ background: "#222", color: "#fff", padding: 12, borderRadius: 8, maxHeight: 300, overflow: "auto" }}>{fixturesJson}</pre>
                    <button onClick={copyFixtures} style={{ marginTop: 8 }}>Kopiuj fixtures</button>
                </div>
            )}
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
                    disabled={loading || status === 'done'}
                    placeholder={loading ? "Czekaj..." : "Napisz wiadomość..."}
                    style={{ flex: 1, padding: 8, borderRadius: 4, border: "1px solid #ccc" }}
                />
                <button type="submit" disabled={loading || !input.trim() || status === 'done'} style={{ padding: "8px 16px" }}>
                    Wyślij
                </button>
            </form>
            {dataCompleted && status !== 'done' && (
                <button onClick={handleGenerate} disabled={loading} style={{ marginTop: 16, padding: "8px 16px", background: "#007bff", color: "#fff", border: "none", borderRadius: 4 }}>
                    Wygeneruj fixtures
                </button>
            )}
        </div>
    );
};

export default GptChatWindow;