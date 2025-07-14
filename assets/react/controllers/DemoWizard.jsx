import React, {useEffect, useCallback, useContext} from 'react';
import {WizardContext} from '../hooks/WizardProvider';
import {motion, AnimatePresence} from 'framer-motion';
import {useNavigate, useParams} from 'react-router-dom';
import {useSupportedPlugins} from '../hooks/useSupportedPlugins';
import {useConversation} from './DescribeStoreStage/hooks/useConversation';
import {useStorePreset} from '../hooks/useStorePreset';
import PluginsStep from './DemoWizardSteps/PluginsStep';
import InterviewStep from './DemoWizardSteps/InterviewStep';
import InterviewSummaryStep from './DemoWizardSteps/InterviewSummaryStep';
import DeployStep from './DemoWizardSteps/DeployStep';
import StoreSummaryStep from './DemoWizardSteps/StoreSummaryStep';

const steps = [
    'Plugins',
    'Fixtures',
    'Interview summary',
    'Deploy',
    'Store summary',
];

const stepPaths = [
    'choose-plugins',
    'describe-store',
    'interview-summary',
    'choose-deploy',
    'store-summary'
];

const stepTitles = [
    'Choose plugins',
    'Describe your store',
    'Interview summary',
    'Choose deployment target',
    'Store summary',
];

const stepDescriptions = [
    'Plugins are optional. You can select any to include, or proceed without plugins.',
    'Provide a description of your store and its details.',
    'Summary of your store interview and configuration.',
    'Choose where you want to deploy your store.',
    'Review and confirm your settings before launching your demo store.'
];

export default function DemoWizard({apiUrl, environmentsUrl, deployStateUrlBase}) {
    const navigate = useNavigate();
    const {step: stepParam} = useParams();
    const {wiz, dispatch} = useContext(WizardContext);
    const conversation = useConversation();
    const {
        presetId,
        loading: presetLoading,
        error: presetError,
        updatePreset,
    } = useStorePreset();

    useEffect(() => {
        if (!stepParam || stepParam !== stepPaths[wiz.step - 1]) {
            const timer = setTimeout(() => {
                navigate(`/wizard/${stepPaths[wiz.step - 1]}`, {replace: true});
            }, 100);
            return () => clearTimeout(timer);
        }
    }, [wiz.step, stepParam, navigate]);

    useEffect(() => {
        if (!stepParam) {
            navigate(`/wizard/${stepPaths[0]}`, {replace: true});
        }
    }, [stepParam, navigate]);

    useEffect(() => {
        if (wiz.step === 4 && wiz.target === 'platform.sh') {
            fetch(environmentsUrl)
                .then(r => r.json())
                .then(data => dispatch({type: 'SET_ENV_OPTIONS', envOptions: data.environments || []}))
                .catch(() => dispatch({type: 'SET_ERROR', error: 'Failed to fetch environments'}));
        }
    }, [wiz.step, wiz.target, environmentsUrl, dispatch]);

    useEffect(() => {
        if (wiz.step === 5 && wiz.deployStateId) {
            const interval = setInterval(() => {
                fetch(`${deployStateUrlBase}/${wiz.env}/${wiz.deployStateId}`)
                    .then(r => r.json())
                    .then(data => {
                        dispatch({type: 'SET_DEPLOY_STATUS', deployStatus: data.status});
                        if (data.status !== 'in_progress') {
                            clearInterval(interval);
                        }
                    })
                    .catch(() => {
                        dispatch({type: 'SET_ERROR', error: 'Failed to check deploy status'});
                        clearInterval(interval);
                    });
            }, 20000);
            return () => clearInterval(interval);
        }
    }, [wiz.step, wiz.deployStateId, wiz.env, deployStateUrlBase, dispatch]);

    // Reset describeStoreStageReady when step changes away from 2
    useEffect(() => {
        if (wiz.step !== 2 && wiz.isDescribeStoreStageReady) {
            dispatch({type: 'SET_DESCRIBE_STORE_STAGE_READY', isDescribeStoreStageReady: false});
        }
    }, [wiz.step, wiz.isDescribeStoreStageReady, dispatch]);

    // Reset fixtures state when entering step 3 (preview-store)
    useEffect(() => {
        if (wiz.step === 3) {
            // Reset fixtures state to allow regeneration
            dispatch({type: 'SET_FIXTURES_READY', isFixturesReady: false});
            dispatch({type: 'SET_FIXTURES_GENERATING', isFixturesGenerating: false});
        }
    }, [wiz.step, dispatch]);

    const handlePluginsSelected = (plugins) => {
        dispatch({type: 'SET_SELECTED_PLUGINS', selectedPlugins: plugins});
        updatePreset({plugins});
    };

    const handleNext = useCallback(() => {
        dispatch({type: 'SET_STEP', step: Math.min(wiz.step + 1, stepPaths.length), direction: 1});
    }, [wiz.step, dispatch]);
    const back = useCallback(() => {
        dispatch({type: 'SET_STEP', step: Math.max(wiz.step - 1, 1), direction: -1});
    }, [wiz.step, dispatch]);

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
                            navigate(`/wizard/${stepPaths[0]}`, {replace: true});
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
                    {wiz.step === 3 && (
                        <InterviewSummaryStep
                            conversation={conversation}
                            presetId={presetId}
                            updatePreset={updatePreset}
                            handleNext={handleNext}
                        />
                    )}
                    {wiz.step === 4 && (
                        <DeployStep
                            apiUrl={apiUrl}
                            environmentsUrl={environmentsUrl}
                            deployStateUrlBase={deployStateUrlBase}
                            presetId={presetId}
                            updatePreset={updatePreset}
                            handleNext={handleNext}
                        />
                    )}
                    {wiz.step === 5 && (
                        <StoreSummaryStep
                            conversation={conversation}
                            presetId={presetId}
                            updatePreset={updatePreset}
                            handleNext={handleNext}
                        />
                    )}
                </div>
            </div>
        </motion.div>
    );
}
