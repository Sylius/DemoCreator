import React, { useRef, useEffect } from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import rehypeRaw from 'rehype-raw';
import { Prism as SyntaxHighlighter } from 'react-syntax-highlighter';
import { tomorrow } from 'react-syntax-highlighter/dist/esm/styles/prism';

const MessageList = ({ messages, showFunctionMessages, height = 250 }) => {
    const chatEndRef = useRef(null);

    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: "smooth" });
    }, [messages]);

    const filteredMessages = messages.filter(
        m => m.role !== "system" && (showFunctionMessages || m.role !== "function")
    );

    return (
        <div className="flex-1 overflow-y-auto flex flex-col gap-2 pb-2" style={{minHeight: 0}}>
            {filteredMessages.map((msg, idx) => (
                <div
                    key={idx}
                    className={
                        msg.role === "user"
                            ? "self-end border border-gray-200 rounded-xl px-4 py-2 text-base text-gray-900 max-w-[80%]"
                            : "self-start border border-gray-200 rounded-xl px-4 py-2 text-base text-gray-800 max-w-[80%]"
                    }
                    style={{ background: 'none', boxShadow: 'none' }}
                >
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
            ))}
            <div ref={chatEndRef} />
        </div>
    );
};

export default MessageList; 