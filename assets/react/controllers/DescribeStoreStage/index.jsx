import React from 'react';
import { useConversation } from './hooks/useConversation';
import ConversationPanel from './ConversationPanel';

const DescribeStoreStage = ({ onStoreDetailsChange }) => {
    const {
        messages,
        input,
        loading,
        error,
        storeDetails,
        setInput,
        handleSend,
        copyConversation,
        handleCreateFixtures,
    } = useConversation();

    // Informuj rodzica o zmianie storeDetails
    React.useEffect(() => {
        if (typeof onStoreDetailsChange === 'function') {
            onStoreDetailsChange(storeDetails);
        }
    }, [storeDetails, onStoreDetailsChange]);

    return (
        <div style={{
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'flex-start',
            minHeight: '60vh',
            margin: '2rem 0',
        }}>
            <div style={{
                maxWidth: 1000,
                width: '100%',
                margin: '0 auto',
                flex: '0 1 1000px',
                zIndex: 1
            }}>
                <ConversationPanel
                    messages={messages}
                    input={input}
                    setInput={setInput}
                    handleSend={handleSend}
                    loading={loading}
                    error={error}
                    copyConversation={copyConversation}
                    handleCreateFixtures={handleCreateFixtures}
                />
            </div>
        </div>
    );
};

export default DescribeStoreStage;