import React, {useState, useRef, useEffect} from "react";

const GptChatWindow = ({onNext}) => {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [ready, setReady] = useState(false);
    const [dataCompleted, setDataCompleted] = useState(false);
    const [storeInfo, setStoreInfo] = useState(null);
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

    useEffect(() => {
      if (conversationId) {
        fetch(`/api/gpt-chat/state?conversation_id=${conversationId}`)
          .then(res => res.json())
          .then(data => {
            if ('dataCompleted' in data) {
              setDataCompleted(data.dataCompleted);
              setReady(data.dataCompleted);
            }
            if (data.storeInfo) {
              setStoreInfo(data.storeInfo);
            }
            if (Array.isArray(data.messages)) {
              setMessages(data.messages);
            }
          })
          .catch(console.error);
      }
    }, [conversationId]);

    const handleSend = async (e) => {
        e.preventDefault();
        if (!input.trim()) return;
        setError(null);
        const newMessages = [...messages, {role: "user", content: input}];
        setMessages(newMessages);
        setInput("");
        setLoading(true);
        try {
            const response = await fetch("/api/gpt-chat", {
                method: "POST",
                headers: {"Content-Type": "application/json"},
                body: JSON.stringify({
                    conversation_id: conversationId,
                    messages: newMessages,
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
        setMessages([]);
        setInput("");
        setError(null);
        setLoading(false);
        setReady(false);
        setDataCompleted(false);
        setStoreInfo(null);
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
                {messages.filter(m => m.role !== "system").map((msg, idx) => (
                    <div key={idx} style={{margin: "8px 0", textAlign: msg.role === "user" ? "right" : "left"}}>
            <span style={{
                display: "inline-block",
                padding: "8px 12px",
                borderRadius: 16,
                background: msg.role === "user" ? "#d1e7dd" : "#e2e3e5",
                color: "#222"
            }}>
              {msg.content}
            </span>
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
        </div>
    );
};

export default GptChatWindow;