import React, { useEffect, useRef } from 'react';
import { useConversation } from './hooks/useConversation';
import ConversationPanel from './ConversationPanel';
import StoreDetailsPanel from './StoreDetailsPanel';
import { motion, AnimatePresence } from 'framer-motion';

const DescribeStoreStage = ({ onReadyToProceed, onStoreDetailsChange, onNext, isReady }) => {
    const {
        messages,
        input,
        loading,
        error,
        state,
        storeDetails,
        setInput,
        handleSend,
        retryRequest,
        handleCreateFixtures,
        clearConversation,
        copyConversation,
    } = useConversation();

    // Informuj rodzica o zmianie storeDetails
    React.useEffect(() => {
        if (typeof onStoreDetailsChange === 'function') {
            onStoreDetailsChange(storeDetails);
        }
    }, [storeDetails, onStoreDetailsChange]);

    // Ensure callback is only called once per transition to 'awaiting_confirmation'
    const hasCalledRef = useRef(false);
    useEffect(() => {
        if (state === 'awaiting_confirmation' && !hasCalledRef.current) {
            hasCalledRef.current = true;
            if (typeof onReadyToProceed === 'function') {
                onReadyToProceed();
            }
        } else if (state !== 'awaiting_confirmation') {
            hasCalledRef.current = false;
        }
    }, [state, onReadyToProceed]);

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
                    state={state}
                    copyConversation={copyConversation}
                    retryRequest={retryRequest}
                    handleCreateFixtures={handleCreateFixtures}
                    clearConversation={clearConversation}
                    onNext={onNext}
                    isReady={isReady}
                />
            </div>
            {/*<AnimatePresence>*/}
            {/*    {state === 'awaiting_confirmation' && (*/}
            {/*        <motion.div*/}
            {/*            key="store-details-panel"*/}
            {/*            initial={{ opacity: 0, x: 40 }}*/}
            {/*            animate={{ opacity: 1, x: 0 }}*/}
            {/*            exit={{ opacity: 0, x: 40 }}*/}
            {/*            transition={{ duration: 0.4 }}*/}
            {/*            style={{ position: 'relative', zIndex: 2 }}*/}
            {/*        >*/}
            {/*            <StoreDetailsPanel storeDetails={storeDetails}/>*/}
            {/*        </motion.div>*/}
            {/*    )}*/}
            {/*</AnimatePresence>*/}
        </div>
    );
};

export default DescribeStoreStage; 