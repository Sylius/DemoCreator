import {useState, useContext, useEffect} from 'react';
import MessageList from './MessageList';
import MessageInput from './MessageInput';
import {WizardContext} from '../../../hooks/WizardProvider';

const ConversationPanel = ({
                               messages,
                               input,
                               setInput,
                               handleSend,
                               loading,
                               error,
                               conversationState,
                               copyConversation,
                           }) => {
    const [showFunctionMessages, setShowFunctionMessages] = useState(false);
    const {wiz, dispatch} = useContext(WizardContext);

    const onNext = () => {
        dispatch({type: 'NEXT_STEP'});
    }

    return (
        <div className="flex flex-col flex-1 min-h-0 bg-gray-50">
            {conversationState === 'done' && (
                <div
                    className="flex items-center justify-center py-4 px-4 mb-4 bg-green-50 border-2 border-green-200 rounded-xl mx-2">
                    <span className="w-3 h-3 rounded-full bg-green-500 mr-3"></span>
                    <strong className="text-green-700 text-sm font-medium">All data collected!</strong>
                </div>
            )}
            <MessageList
                messages={messages}
                showFunctionMessages={showFunctionMessages}
                height={undefined}
                loading={loading}
            />

            {/* Large centered Next button when ready */}
            {wiz.state === 'ready' && (
                <div className="flex justify-center items-center py-6 px-4">
                    <button
                        onClick={onNext}
                        className="w-full max-w-lg py-4 px-8 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl font-semibold shadow-lg transition-all duration-200 transform hover:scale-105 border-2 border-teal-500"
                    >
                        Next →
                    </button>
                </div>
            )}
            {error && (
                <div className="mx-2 mb-4 p-3 bg-red-50 border-2 border-red-200 rounded-xl">
                    <strong className="text-red-700 text-sm">Error:</strong>
                    <span className="text-red-600 text-sm ml-1">{error}</span>
                </div>
            )}
            <div
                className="px-4 pb-4 pt-2 bg-white sticky bottom-0 z-10 rounded-3xl border-t border-gray-200">
                <MessageInput
                    input={input}
                    setInput={setInput}
                    handleSend={handleSend}
                    loading={loading}
                    disabled={conversationState === 'done'}
                    messages={messages}
                    autoFocus={true}
                />
            </div>
        </div>
    );
};

export default ConversationPanel;
