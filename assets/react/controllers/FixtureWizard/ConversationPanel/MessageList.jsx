import React, { useRef, useEffect } from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import rehypeRaw from 'rehype-raw';
import { Prism as SyntaxHighlighter } from 'react-syntax-highlighter';
import { tomorrow } from 'react-syntax-highlighter/dist/esm/styles/prism';

const MessageList = ({ messages, showFunctionMessages, height = 650 }) => {
    const chatEndRef = useRef(null);

    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: "smooth" });
    }, [messages]);

    const filteredMessages = messages.filter(
        m => m.role !== "system" && (showFunctionMessages || m.role !== "function")
    );

    return (
        <div style={{
            height,
            overflowY: "auto",
            marginBottom: 12,
            background: "#f9f9f9",
            padding: 8,
            borderRadius: 4
        }}>
            {filteredMessages.map((msg, idx) => (
                <div key={idx} style={{ margin: "8px 0", textAlign: msg.role === "user" ? "right" : "left" }}>
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
                                    code({ node, inline, className, children, ...props }) {
                                        const match = /language-(\w+)/.exec(className || '');
                                        return !inline && match ? (
                                            <SyntaxHighlighter 
                                                style={tomorrow} 
                                                language={match[1]} 
                                                PreTag="div" 
                                                {...props}
                                            >
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
    );
};

export default MessageList; 