import { useState, useRef, useEffect, useContext } from 'react';
import { WizardContext } from '../../../hooks/WizardProvider';

export function useConversation() {
    const { wiz, dispatch } = useContext(WizardContext);

    const [messages, setMessages] = useState(() => {
        const stored = localStorage.getItem('messages');
        return stored ? JSON.parse(stored) : [];
    });
    const [input, setInput] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [conversationId, setConversationId] = useState(() => {
        return localStorage.getItem('conversationId') || null;
    });

    // Persist state to localStorage
    useEffect(() => {
        if (conversationId) {
            localStorage.setItem('conversationId', conversationId);
        }
    }, [conversationId]);

    useEffect(() => {
        localStorage.setItem('messages', JSON.stringify(messages));
    }, [messages]);

    // Auto-send next request if last message is function_call
    useEffect(() => {
        if (
            messages.length > 0 &&
            messages[messages.length - 1].function_call &&
            !loading
        ) {
            handleSend(null, true);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [messages]);

    const handleSend = async (e, auto = false) => {

        if (!auto) e?.preventDefault();
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
                storeDetails: wiz.storeDetails,
                state: wiz.state,
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
                dispatch({ type: 'SET_WIZARD_STATE', state: { state: 'error' } });
                setLoading(false);
                return;
            }
            
            if (data.error) setError(data.error); 
            else setError(null);
            
            if (data.state) {
                console.log(data.state);
                dispatch({ type: 'SET_WIZARD_STATE', state: { state: data.state } });
            }
            if (data.conversationId) setConversationId(data.conversationId);
            if (data.storeDetails) {
                dispatch({ type: 'UPDATE_STORE_DETAILS', storeDetails: data.storeDetails });
            }
            
            if (Array.isArray(data.messages)) {
                setMessages(data.messages);
            } else {
                setError(data.details);
            }
        } catch (err) {
            setError(err.message || "Unknown error");
            dispatch({ type: 'SET_WIZARD_STATE', state: { state: 'error' } });
        } finally {
            setLoading(false);
        }
    };

    const retryRequest = async () => {
        setLoading(true);
        try {
            const payload = {
                conversationId,
                messages,
                storeDetails: wiz.storeDetails,
                state: wiz.state,
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
                dispatch({ type: 'SET_WIZARD_STATE', state: { state: 'error' } });
                setLoading(false);
                return;
            }
            
            if (data.error) setError(data.error); 
            else setError(null);
            
            if (data.state) dispatch({ type: 'SET_WIZARD_STATE', state: { state: data.state } });
            if (data.conversationId) setConversationId(data.conversationId);

            if (Array.isArray(data.messages)) {
                setMessages(data.messages);
            } else {
                setError("Missing 'messages' in API response");
            }
        } catch (err) {
            setError(err.message || "Unknown error");
            dispatch({ type: 'SET_WIZARD_STATE', state: { state: 'error' } });
        } finally {
            setLoading(false);
        }
    };

    const clearConversation = () => {
        setConversationId(null);
        localStorage.removeItem('conversationId');
        localStorage.removeItem('messages');
        dispatch({ type: 'SET_WIZARD_STATE', state: { state: 'collecting' } });
        setMessages([]);
        setInput("");
        setError(null);
        setLoading(false);
    };

    const copyConversation = () => {
        const payload = JSON.stringify(
            { conversationId, messages, storeDetails: wiz.storeDetails, state: wiz.state, error },
            null,
            2
        );
        navigator.clipboard.writeText(payload)
            .then(() => alert("Conversation copied to clipboard!"))
            .catch(() => alert("Failed to copy conversation."));
    };

    return {
        messages,
        input,
        loading,
        error,
        storeDetails: wiz.storeDetails,
        conversationId,
        state: wiz.state,
        // Actions
        setInput,
        handleSend,
        retryRequest,
        clearConversation,
        copyConversation,
    };
}
