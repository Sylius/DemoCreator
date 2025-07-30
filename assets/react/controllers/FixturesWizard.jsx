import React, { useState, useRef, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";

export default function FixturesWizard() {
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState("");
  const [started, setStarted] = useState(false);
  const messagesEndRef = useRef(null);

  useEffect(() => {
    if (started && messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: "smooth" });
    }
  }, [messages, started]);

  const handleSend = () => {
    if (!input.trim()) return;
    setMessages(msgs => [...msgs, { text: input, from: "user" }]);
    setInput("");
    setStarted(true);
    // Możesz dodać obsługę odpowiedzi asystenta tutaj
  };

  return (
    <div className="w-full flex items-center justify-center min-h-[70vh]">
      <AnimatePresence initial={false}>
        <motion.div
          key={started ? "chat" : "prompt"}
          initial={{
            opacity: 0,
            scale: 0.95,
            y: 40,
            height: started ? 320 : 320
          }}
          animate={{
            opacity: 1,
            scale: 1,
            y: 0,
            height: started ? "70vh" : 320
          }}
          exit={{
            opacity: 0,
            scale: 0.95,
            y: 40
          }}
          transition={{
            type: "spring",
            stiffness: 300,
            damping: 30
          }}
          className={`bg-white rounded-2xl shadow-xl w-full max-w-xl flex flex-col overflow-hidden transition-all duration-300 border border-gray-100`}
          layout
        >
          <div className={`flex-1 flex flex-col ${started ? "overflow-y-auto p-6" : "justify-center items-center p-8"}`}>
            {!started ? (
              <>
                <h2 className="text-2xl font-bold mb-4 text-center">What's on the agenda today?</h2>
                <p className="text-gray-500 mb-8 text-center">Ask anything</p>
              </>
            ) : (
              <div className="flex flex-col gap-4 w-full">
                {messages.map((msg, i) => (
                  <motion.div
                    key={i}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.25, delay: i * 0.05 }}
                    className={`rounded-lg px-4 py-2 max-w-[80%] break-words shadow-sm ${
                      msg.from === "user"
                        ? "bg-teal-100 self-end text-right"
                        : "bg-gray-100 self-start text-left"
                    }`}
                  >
                    {msg.text}
                  </motion.div>
                ))}
                <div ref={messagesEndRef} />
              </div>
            )}
          </div>
          <form
            className="flex items-center gap-2 border-t border-gray-100 p-4 bg-white"
            onSubmit={e => {
              e.preventDefault();
              handleSend();
            }}
          >
            <input
              className="flex-1 px-4 py-2 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-teal-200"
              placeholder="Ask anything"
              value={input}
              onChange={e => setInput(e.target.value)}
              autoFocus
            />
            <button
              type="submit"
              className="bg-teal-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-teal-700 transition"
            >
              Send
            </button>
          </form>
        </motion.div>
      </AnimatePresence>
    </div>
  );
}
