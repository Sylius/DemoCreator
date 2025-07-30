import InterviewStep from './DemoWizardSteps/InterviewStep';
import InterviewSummaryStep from './DemoWizardSteps/InterviewSummaryStep';
import PluginsStep from './DemoWizardSteps/PluginsStep';
import React, {useContext} from 'react';
import StoreSummaryStep from './DemoWizardSteps/StoreSummaryStep';
import {motion} from 'framer-motion';
import {useNavigate, useParams} from 'react-router-dom';
import {useStorePreset} from '../hooks/useStorePreset';
import {WizardContext} from '../hooks/WizardProvider';

const steps = [
    'Plugins',
    'Fixtures',
    'Interview summary',
    'Store summary',
];

const stepPaths = [
    'choose-plugins',
    'describe-store',
    'interview-summary',
    'store-summary'
];

const stepTitles = [
    'Choose plugins',
    'Describe your store',
    'Interview summary',
    'Store summary',
];

const stepDescriptions = [
    'Plugins are optional. You can select any to include, or proceed without plugins.',
    'Provide a description of your store and its details.',
    'Summary of your store interview and configuration.',
    'Confirm your store and proceed to generation.',
];

export default function DemoWizard() {
    const navigate = useNavigate();
    const {step: stepParam} = useParams();
    const {wiz, dispatch} = useContext(WizardContext);
    const {presetId} = useStorePreset();

    return (
        <motion.div
            initial={{opacity: 0, scale: 0.98, y: 30}}
            animate={{opacity: 1, scale: 1, y: 0}}
            transition={{duration: 0.5, ease: 'easeOut'}}
            style={{padding: 0}}
        >
            <div className="mb-0 sticky top-0 z-10">
                <div className="flex items-center justify-between px-4 mt-4">
                    <button
                        onClick={() => {
                            dispatch({type: 'RESET_WIZARD'});
                            navigate(`/`, {replace: true});
                            window.location.reload();
                        }}
                        className="text-gray-500 hover:text-gray-700 text-sm underline"
                        title="Start over"
                    >
                        Start over
                    </button>
                    <div className="flex items-center gap-2">
                        {steps.map((label, idx) => (
                            <span key={label}
                                  className={`h-2 w-2 rounded-full transition-colors duration-200 ${wiz.step === idx + 1 ? 'bg-teal-600' : 'bg-gray-200'}`}></span>
                        ))}
                    </div>
                    <div className="text-sm text-gray-500">
                        {presetId && `ID: ${presetId.slice(-8)}`}
                    </div>
                </div>
                <h2 className="text-2xl font-bold text-center mt-6 mb-2">{stepTitles[wiz.step - 1]}</h2>
                {stepDescriptions[wiz.step - 1] && (
                    <p className="text-gray-500 text-center mb-4">{stepDescriptions[wiz.step - 1]}</p>
                )}
                {wiz.error && (
                    <div className="mx-4 mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p className="text-red-700 text-sm">{wiz.error}</p>
                        <button
                            onClick={() => dispatch({type: 'SET_ERROR', error: null})}
                            className="text-red-500 hover:text-red-700 text-xs underline mt-1"
                        >
                            Dismiss
                        </button>
                    </div>
                )}
            </div>
            <div className="flex-1 min-h-0 flex flex-col">
                <div className="flex-1 overflow-y-auto min-h-0 relative">
                    {wiz.step === 1 && (<PluginsStep/>)}
                    {wiz.step === 2 && (<InterviewStep/>)}
                    {wiz.step === 3 && (<InterviewSummaryStep/>)}
                    {wiz.step === 4 && (<StoreSummaryStep/>)}
                </div>
            </div>
        </motion.div>
    );
}
