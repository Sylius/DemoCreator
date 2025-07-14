import { useState, useRef, useEffect } from 'react';
import {useWizardState} from "../../../hooks/useWizardState";

export function useConversation() {
    const [wiz, dispatch] = useWizardState();

    const [messages, setMessages] = useState(() => {
        const stored = localStorage.getItem('messages');
        return stored ? JSON.parse(stored) : [];
    });
    const [input, setInput] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [storeDetails, setStoreDetails] = useState(() => {
        const stored = localStorage.getItem('storeDetails');
        return stored ? JSON.parse(stored) : null;
    });
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

    useEffect(() => {
        if (storeDetails !== null) {
            localStorage.setItem('storeDetails', JSON.stringify(storeDetails));
        } else {
            localStorage.removeItem('storeDetails');
        }
    }, [storeDetails]);

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
                storeDetails,
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
            if (data.storeDetails) setStoreDetails(data.storeDetails);
            
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

    const retryRequest = async () => {
        setLoading(true);
        try {
            const payload = {
                conversationId,
                messages,
                storeDetails,
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
            if (data.storeDetails) setStoreDetails(data.storeDetails);
            
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

    const handleCreateFixtures = async (presetId, overrideStoreDetails) => {
        const details = overrideStoreDetails !== undefined ? overrideStoreDetails : storeDetails;
        if (!details) {
            setError('Store details are required. Please complete the store description first.');
            return;
        }
        if (!presetId) {
            setError('Brak presetId!');
            return;
        }
        setError(null);
        setLoading(true);
        const payload = { storeDetails: details };
        try {
            const response = await fetch(`/api/store-presets/${encodeURIComponent(presetId)}/fixtures-generate`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const rawResponse = await response.text();
            let data;
            try {
                data = JSON.parse(rawResponse);
            } catch (parseError) {
                setError(`Invalid JSON response from API:\n${rawResponse}`);
                dispatch({ type: 'SET_WIZARD_STATE', state: { state: 'error' } });
                return;
            }
            if (data.error) setError(data.error); 
            else setError(null);
            // Możesz tu dodać obsługę success, np. dispatch({ type: 'SET_WIZARD_STATE', state: { state: 'fixtures_ready' } })
        } catch (err) {
            setError(err.message || 'Unknown error');
            dispatch({ type: 'SET_WIZARD_STATE', state: { state: 'error' } });
        } finally {
            setLoading(false);
        }
    };

    const clearConversation = () => {
        setConversationId(null);
        localStorage.removeItem('conversationId');
        localStorage.removeItem('messages');
        localStorage.removeItem('storeDetails');
        dispatch({ type: 'SET_WIZARD_STATE', state: { state: 'collecting' } });
        setStoreDetails(null);
        setMessages([]);
        setInput("");
        setError(null);
        setLoading(false);
    };

    const copyConversation = () => {
        const payload = JSON.stringify(
            { conversationId, messages, storeDetails, state: wiz.state, error },
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
        storeDetails,
        conversationId,
        state: wiz.state,
        // Actions
        setInput,
        handleSend,
        retryRequest,
        handleCreateFixtures,
        clearConversation,
        copyConversation,
    };
}