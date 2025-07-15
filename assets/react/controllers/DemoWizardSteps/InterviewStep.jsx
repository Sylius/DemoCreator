import React, {useCallback, useContext} from 'react';
import {WizardContext} from '../../hooks/WizardProvider';
import {motion} from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';
import ConversationPanel from "../DescribeStoreStage/ConversationPanel";
import {useConversation} from "../DescribeStoreStage/hooks/useConversation";

export default function InterviewStep() {
    const {wiz, dispatch} = useContext(WizardContext);
    const {messages, input, loading, error, setInput, handleSend, copyConversation} = useConversation();

    const back = useCallback(() => {
        dispatch({ type: 'SET_STEP', step: Math.max(wiz.step - 1, 1), direction: -1 });
    }, [wiz.step, dispatch]);

    return (
        <motion.div
            key="2"
            custom={wiz.direction}
            variants={wizardStepVariants}
            initial="enter"
            animate="center"
            exit="exit"
            transition={{duration: 0.25, type: 'tween', ease: 'easeInOut'}}
        >
            <div className="flex flex-row w-full min-h-[70vh] gap-6">
                <div className="flex-1 flex flex-col min-h-0">
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
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div className="flex justify-between mt-6">
                <button onClick={back}
                        className="text-teal-600 hover:underline rounded-lg px-4 py-2">←
                    Back
                </button>
            </div>
        </motion.div>
    );
}
