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
    const [ready, setReady] = useState(false);
    const [dataCompleted, setDataCompleted] = useState(false);
    const [storeInfo, setStoreInfo] = useState(() => {
        const stored = localStorage.getItem('storeInfo');
        return stored ? JSON.parse(stored) : null;
    });
    const [showFunctionMessages, setShowFunctionMessages] = useState(false);
    const [fixturesJson, setFixturesJson] = useState(null);
    // Initialize conversationId from localStorage to persist across refreshes
    const [conversationId, setConversationId] = useState(() => {
        return localStorage.getItem('conversation_id') || null;
    });
    const chatEndRef = useRef(null);

    useEffect(() => {
        chatEndRef.current?.scrollIntoView({behavior: "smooth"});
    }, [messages, loading]);

    // Persist conversationId to localStorage whenever it changes
    useEffect(() => {
        if (conversationId) {
            localStorage.setItem('conversation_id', conversationId);
        }
    }, [conversationId]);

    // Persist messages to localStorage whenever they change
    useEffect(() => {
        localStorage.setItem('messages', JSON.stringify(messages));
    }, [messages]);

    // Persist storeInfo to localStorage whenever it changes
    useEffect(() => {
        if (storeInfo !== null) {
            localStorage.setItem('storeInfo', JSON.stringify(storeInfo));
        } else {
            localStorage.removeItem('storeInfo');
        }
    }, [storeInfo]);


    const handleSend = async (e) => {
        e.preventDefault();
        if (!input.trim()) return;
        setError(null);
        const newMessages = [...messages, {role: "user", content: input}];
        setMessages(newMessages);
        setInput("");
        setLoading(true);
        try {
            const payloadMessages = [...newMessages];
            const response = await fetch("/api/gpt-chat", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({
                    conversation_id: conversationId,
                    messages: payloadMessages,
                }),
            });
            const data = await response.json();
            if ('dataCompleted' in data) {
                setDataCompleted(data.dataCompleted);
                setReady(data.dataCompleted);
            }
            if (data.conversation_id) {
                setConversationId(data.conversation_id);
            }
            if (data.storeInfo) {
              setStoreInfo(data.storeInfo);
            }
            if (data.error) throw new Error(data.error);
            if (Array.isArray(data.messages)) {
                setMessages(data.messages);
            } else if (Array.isArray(data.choices) && data.choices[0]?.message) {
                const assistantMsg = data.choices[0].message;
                setMessages((msgs) => [...msgs, assistantMsg]);
            } else {
                setError("Brak 'messages' lub 'choices' w odpowiedzi API");
            }
        } catch (err) {
            setError(err.message || "Unknown error");
        } finally {
            setLoading(false);
        }
    };
    const copyConversation = () => {
      const payload = JSON.stringify(
        { conversation_id: conversationId, messages },
        null,
        2
      );
      navigator.clipboard.writeText(payload)
        .then(() => alert("Rozmowa skopiowana do schowka!"))
        .catch(() => alert("Nie udało się skopiować rozmowy."));
    };
    const clearConversation = () => {
        // Reset conversation state and clear storage
        setConversationId(null);
        localStorage.removeItem('conversation_id');
        localStorage.removeItem('messages');
        localStorage.removeItem('storeInfo');
        setStoreInfo(null);
        setMessages([]);
        setInput("");
        setError(null);
        setLoading(false);
        setReady(false);
        setDataCompleted(false);
    };

  const handleGenerate = async () => {
    setError(null);
    setLoading(true);
    try {
      const payloadMessages = [...messages];
      const response = await fetch("/api/gpt-chat", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
          conversation_id: conversationId,
          messages: payloadMessages,
          generateFixtures: true,
        }),
      });
      const data = await response.json();
      if (data.fixtures) {
        const json = JSON.stringify(data.fixtures, null, 2);
        setFixturesJson(json);
      } else if (data.error) {
        throw new Error(data.error);
      } else {
        throw new Error("Brak fixtures w odpowiedzi API");
      }
    } catch (err) {
      setError(err.message || "Unknown error generating fixtures");
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
            {ready && (
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
                {loading && <div style={{color: "#888", fontStyle: "italic"}}>Assistant is typing...</div>}
                <div ref={chatEndRef}/>
            </div>
            {error && <div style={{color: "#b00", marginBottom: 8}}>{error}</div>}
            <form onSubmit={handleSend} style={{display: "flex", gap: 8}}>
                <input
                    type="text"
                    value={input}
                    onChange={e => setInput(e.target.value)}
                    placeholder="Type your message..."
                    disabled={loading}
                    style={{flex: 1, padding: 8, borderRadius: 4, border: "1px solid #ccc"}}
                />
                <button type="submit" disabled={loading || !input.trim()} style={{
                    padding: "8px 16px",
                    borderRadius: 4,
                    border: "none",
                    background: "#007bff",
                    color: "#fff"
                }}>
                    Send
                </button>
            </form>
            {!dataCompleted && (
              <button
                type="button"
                onClick={onNext}
                disabled={!ready}
                style={{
                  marginTop: 8,
                  padding: ready ? "12px 24px" : "8px 16px",
                  borderRadius: 4,
                  border: "none",
                  background: ready ? "#28a745" : "#ccc",
                  color: "#fff",
                  cursor: ready ? "pointer" : "not-allowed",
                  fontSize: ready ? "1.25rem" : "1rem"
                }}
              >
                Dalej
              </button>
            )}
            {dataCompleted && (
              <div style={{ display: "flex", gap: 12, marginTop: 8 }}>
                <button
                  type="button"
                  onClick={handleGenerate}
                  style={{
                    padding: "12px 24px",
                    borderRadius: 4,
                    border: "none",
                    background: "#007bff",
                    color: "#fff",
                    cursor: loading || fixturesJson ? "not-allowed" : "pointer",
                    fontSize: "1rem"
                  }}
                >
                  Generuj
                </button>
                {fixturesJson && (
                  <button
                    type="button"
                    onClick={copyFixtures}
                    style={{
                      padding: "12px 24px",
                      borderRadius: 4,
                      border: "none",
                      background: "#17a2b8",
                      color: "#fff",
                      cursor: "pointer",
                      fontSize: "1rem"
                    }}
                  >
                    Kopiuj JSON fixtures
                  </button>
                )}
              </div>
            )}
        </div>
    );
};

export default GptChatWindow;