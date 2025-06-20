import React, { useState, useRef, useEffect } from "react";

const GptChatWindow = ({ onNext }) => {
  const [messages, setMessages] = useState([
    {
      role: "system",
      content: `You are an AI assistant that gathers Sylius shop data step by step and then emits a final JSON fixtures via generate_fixtures(). Answer in natural language until all data are collected.`
    }
  ]);
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [ready, setReady] = useState(false);
  const chatEndRef = useRef(null);

  useEffect(() => {
    chatEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages, loading]);

  const handleSend = async (e) => {
    e.preventDefault();
    if (!input.trim()) return;
    setError(null);
    const newMessages = [...messages, { role: "user", content: input }];
    setMessages(newMessages);
    setInput("");
    setLoading(true);
    try {
      const response = await fetch("/api/gpt-chat", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ messages: newMessages }),
      });
      const data = await response.json();
      if (data.error) throw new Error(data.error);
      // OpenAI API returns choices[0].message
      const assistantMsg = data.choices?.[0]?.message;
      if (assistantMsg) {
        setMessages((msgs) => [...msgs, assistantMsg]);
        // mark as ready when final JSON is produced
        if (assistantMsg.function_call?.name === 'generate_fixtures') {
          setReady(true);
        }
      } else {
        setError("No response from assistant.");
      }
    } catch (err) {
      setError(err.message || "Unknown error");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ maxWidth: 500, margin: "2rem auto", border: "1px solid #ccc", borderRadius: 8, padding: 16, background: "#fff", boxShadow: "0 2px 8px #0001" }}>
      <div style={{ height: 350, overflowY: "auto", marginBottom: 12, background: "#f9f9f9", padding: 8, borderRadius: 4 }}>
        {messages.filter(m => m.role !== "system").map((msg, idx) => (
          <div key={idx} style={{ margin: "8px 0", textAlign: msg.role === "user" ? "right" : "left" }}>
            <span style={{
              display: "inline-block",
              padding: "8px 12px",
              borderRadius: 16,
              background: msg.role === "user" ? "#d1e7dd" : "#e2e3e5",
              color: "#222"
            }}>
              {msg.content
                ? msg.content
                : msg.function_call
                  ? (msg.function_call.name === 'generate_fixtures'
                      ? '✓ Wszystkie dane zebrane.'
                      : '⏳ Trwa przetwarzanie…')
                  : null}
            </span>
          </div>
        ))}
        {loading && <div style={{ color: "#888", fontStyle: "italic" }}>Assistant is typing...</div>}
        <div ref={chatEndRef} />
      </div>
      {error && <div style={{ color: "#b00", marginBottom: 8 }}>{error}</div>}
      <form onSubmit={handleSend} style={{ display: "flex", gap: 8 }}>
        <input
          type="text"
          value={input}
          onChange={e => setInput(e.target.value)}
          placeholder="Type your message..."
          disabled={loading}
          style={{ flex: 1, padding: 8, borderRadius: 4, border: "1px solid #ccc" }}
        />
        <button type="submit" disabled={loading || !input.trim()} style={{ padding: "8px 16px", borderRadius: 4, border: "none", background: "#007bff", color: "#fff" }}>
          Send
        </button>
      </form>
      <button
        type="button"
        onClick={onNext}
        disabled={!ready}
        style={{
          marginTop: 8,
          padding: "8px 16px",
          borderRadius: 4,
          border: "none",
          background: ready ? "#28a745" : "#ccc",
          color: "#fff",
          cursor: ready ? "pointer" : "not-allowed"
        }}
      >
        Dalej
      </button>
    </div>
  );
};

export default GptChatWindow; 