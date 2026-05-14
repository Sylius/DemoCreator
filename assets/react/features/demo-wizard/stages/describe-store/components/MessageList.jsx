import React, { useRef, useEffect } from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import rehypeRaw from 'rehype-raw';
import { Prism as SyntaxHighlighter } from 'react-syntax-highlighter';
import { tomorrow } from 'react-syntax-highlighter/dist/esm/styles/prism';

const MessageList = ({ messages, showFunctionMessages, height = 250, loading = false }) => {
    const chatEndRef = useRef(null);

    useEffect(() => {
        chatEndRef.current?.scrollIntoView({ behavior: "smooth" });
    }, [messages]);

    const filteredMessages = messages.filter(
        m => m.role !== "system" && 
            (showFunctionMessages || m.role !== "function") &&
            m.content && m.content.trim() !== ""
    );

    return (
        <div className="flex-1 overflow-y-auto flex flex-col gap-3 pb-3 px-2" style={{minHeight: 0}}>
            {filteredMessages.map((msg, idx) => (
                <div
                    key={idx}
                    className={
                        msg.role === "user"
                            ? "self-end border-2 border-teal-200 rounded-2xl px-5 py-3 text-base text-gray-900 max-w-[85%] bg-teal-50"
                            : "self-start border-2 border-gray-300 rounded-2xl px-5 py-3 text-base text-gray-800 max-w-[85%] bg-white shadow-sm"
                    }
                    style={{ boxShadow: '0 1px 3px rgba(0,0,0,0.1)' }}
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
            
            {/* Loading spinner in assistant bubble */}
            {loading && (
                <div className="self-start border-2 border-gray-300 rounded-2xl px-5 py-3 text-base text-gray-800 max-w-[85%] bg-white shadow-sm">
                    <div className="flex items-center space-x-3">
                        <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-teal-600"></div>
                        <span className="text-gray-600 font-medium">Responding...</span>
                    </div>
                </div>
            )}
            
            <div ref={chatEndRef} />
        </div>
    );
};

export default MessageList; 