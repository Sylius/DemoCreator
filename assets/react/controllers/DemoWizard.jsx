import React, {useEffect, useCallback} from 'react';
import { useWizardState } from '../hooks/useWizardState';
import {motion, AnimatePresence} from 'framer-motion';
import {useNavigate, useParams} from 'react-router-dom';
import DescribeStoreStage from './DescribeStoreStage';
import {useSupportedPlugins} from '../hooks/useSupportedPlugins';
import {useConversation} from './DescribeStoreStage/hooks/useConversation';
import {useStorePreset} from '../hooks/useStorePreset';

const stepVariants = {
    enter: (direction) => ({
        opacity: 0,
        x: direction > 0 ? 300 : -300,
        position: 'absolute',
        width: '100%',
        top: 0,
        left: 0,
        zIndex: 1
    }),
    center: {
        opacity: 1,
        x: 0,
        position: 'relative',
        width: '100%',
        zIndex: 2
    },
    exit: (direction) => ({
        opacity: 0,
        x: direction > 0 ? -300 : 300,
        position: 'absolute',
        width: '100%',
        top: 0,
        left: 0,
        zIndex: 1
    })
};

const steps = [
    'Plugins',
    'Fixtures',
    'Preview your store',
    'Logo',
    'Deploy',
    'Summary',
];

const stepPaths = [
    'choose-plugins',
    'describe-store',
    'summary',
    'upload-logo',
    'choose-deploy',
    'summary-final'
];

const stepTitles = [
    'Choose plugins',
    'Describe your store',
    'Summary',
    'Choose deployment target',
    'Summary',
];

const stepDescriptions = [
    'Plugins are optional. You can select any to include, or proceed without plugins.',
    'Provide a description of your store and its details.',
    'Generating your store, please wait...',
    'Choose where you want to deploy your store.',
    'Review and confirm your settings before launching your demo store.'
];

const PLUGIN_LABELS = {
    'cms-plugin': 'CMS',
    'customer-service-plugin': 'Customer Service',
    'invoicing-plugin': 'Invoicing',
    'loyalty-plugin': 'Loyalty',
    'refund-plugin': 'Refund',
    'return-plugin': 'Return',
    // ...dodaj kolejne jeśli chcesz
};

function prettify(name) {
    return name
        .replace(/^sylius\//, '')
        .replace(/-plugin$/, '')
        .replace(/-/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
}

export default function DemoWizard({
    apiUrl,
    logoUploadUrl,
    environmentsUrl,
    deployStateUrlBase
}) {
    const navigate = useNavigate();
    const {step: stepParam} = useParams();
    const initialStepIndex = stepPaths.indexOf(stepParam) !== -1 ? stepPaths.indexOf(stepParam) : 0;
    const [wiz, dispatch] = useWizardState();
    const {plugins, loading: pluginsLoading, error: pluginsError, refetch} = useSupportedPlugins();
    const conversation = useConversation();
    const {handleCreateFixtures} = conversation;
    const {
        presetId,
        preset,
        loading: presetLoading,
        error: presetError,
        updatePreset,
        getPreset,
        deletePreset,
    } = useStorePreset();

    // No need to load storeDetails from localStorage; useWizardState persists state.

    // Synchronize step with URL with delay to allow animation
    useEffect(() => {
        if (!stepParam || stepParam !== stepPaths[wiz.step - 1]) {
            const timer = setTimeout(() => {
                navigate(`/wizard/${stepPaths[wiz.step - 1]}`, {replace: true});
            }, 100); // Small delay to allow animation to start
            return () => clearTimeout(timer);
        }
    }, [wiz.step, stepParam, navigate]);

    // Redirect /wizard to first step
    useEffect(() => {
        if (!stepParam) {
            navigate(`/wizard/${stepPaths[0]}`, {replace: true});
        }
    }, [stepParam, navigate]);

    useEffect(() => {
        if (wiz.step === 4 && wiz.target === 'platform.sh') {
            fetch(environmentsUrl)
                .then(r => r.json())
                .then(data => dispatch({ type: 'SET_ENV_OPTIONS', envOptions: data.environments || [] }))
                .catch(() => dispatch({ type: 'SET_ERROR', error: 'Failed to fetch environments' }));
        }
    }, [wiz.step, wiz.target, environmentsUrl, dispatch]);

    useEffect(() => {
        if (wiz.step === 5 && wiz.deployStateId) {
            const interval = setInterval(() => {
                fetch(`${deployStateUrlBase}/${wiz.env}/${wiz.deployStateId}`)
                    .then(r => r.json())
                    .then(data => {
                        dispatch({ type: 'SET_DEPLOY_STATUS', deployStatus: data.status });
                        if (data.status !== 'in_progress') {
                            clearInterval(interval);
                        }
                    })
                    .catch(() => {
                        dispatch({ type: 'SET_ERROR', error: 'Failed to check deploy status' });
                        clearInterval(interval);
                    });
            }, 20000);
            return () => clearInterval(interval);
        }
    }, [wiz.step, wiz.deployStateId, wiz.env, deployStateUrlBase, dispatch]);

    // Reset describeStoreStageReady when step changes away from 2
    useEffect(() => {
        if (wiz.step !== 2 && wiz.isDescribeStoreStageReady) {
            dispatch({ type: 'SET_DESCRIBE_STORE_STAGE_READY', isDescribeStoreStageReady: false });
        }
    }, [wiz.step, wiz.isDescribeStoreStageReady, dispatch]);

    // Reset fixtures state when entering step 3 (preview-store)
    useEffect(() => {
        if (wiz.step === 3) {
            // Reset fixtures state to allow regeneration
            dispatch({ type: 'SET_FIXTURES_READY', isFixturesReady: false });
            dispatch({ type: 'SET_FIXTURES_GENERATING', isFixturesGenerating: false });
        }
    }, [wiz.step, dispatch]);

    const handleNext = useCallback(() => {
        try {
            // Jeśli jesteśmy na kroku 1 (pluginy), wyślij PATCH z wybranymi pluginami
            if (wiz.step === 1) {
                if (pluginsLoading) {
                    dispatch({ type: 'SET_ERROR', error: 'Please wait for plugins to load' });
                    return;
                }
                if (pluginsError) {
                    dispatch({ type: 'SET_ERROR', error: 'Please fix plugin loading error before proceeding' });
                    return;
                }

                // Konwertuj wybrane pluginy do formatu { "sylius/plugin-name": "^1.0" }
                const pluginsPayload = {};
                wiz.selectedPlugins.forEach(pluginName => {
                    const plugin = plugins.find(p => p.composer === pluginName);
                    if (plugin) {
                        pluginsPayload[plugin.name] = `^${plugin.version}`;
                    }
                });
                updatePreset({plugins: pluginsPayload});
            }

            // Sprawdź czy można przejść dalej
            if (wiz.step === 2 && !wiz.isDescribeStoreStageReady) {
                dispatch({ type: 'SET_ERROR', error: 'Please complete the store description before proceeding' });
                return;
            }

            dispatch({ type: 'SET_ERROR', error: null }); // Clear any previous errors
            dispatch({ type: 'SET_STEP', step: Math.min(wiz.step + 1, stepPaths.length), direction: 1 });
        } catch (err) {
            dispatch({ type: 'SET_ERROR', error: `Failed to proceed: ${err.message}` });
        }
    }, [wiz.step, wiz.selectedPlugins, pluginsLoading, pluginsError, plugins, updatePreset, wiz.isDescribeStoreStageReady, dispatch]);
    const back = useCallback(() => {
        dispatch({ type: 'SET_STEP', step: Math.max(wiz.step - 1, 1), direction: -1 });
    }, [wiz.step, dispatch]);

    // The following handlers dispatch to wizard state instead of using useState/setXxx
    const buildPluginPayload = () => {
        const map = {};
        wiz.selectedPlugins.forEach(composer => {
            const plugin = plugins.find(p => p.composer === composer);
            if (plugin) map[composer] = plugin.version;
        });
        return map;
    };

    const handleDeploy = async (payload) => {
        dispatch({ type: 'SET_LOADING', loading: true });
        dispatch({ type: 'SET_ERROR', error: null });
        try {
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message);
            dispatch({ type: 'SET_DEPLOY_STATE_ID', deployStateId: data.deployStateId });
            dispatch({ type: 'SET_DEPLOY_URL', deployUrl: data.url });
            dispatch({ type: 'SET_DEPLOY_STATUS', deployStatus: 'in_progress' });
            dispatch({ type: 'SET_STEP', step: 5, direction: 1 });
        } catch (e) {
            dispatch({ type: 'SET_ERROR', error: e.message });
        } finally {
            dispatch({ type: 'SET_LOADING', loading: false });
        }
    };

    const handleStoreDetailsConfirm = () => {
        dispatch({ type: 'SET_STEP', step: Math.min(wiz.step + 1, stepPaths.length), direction: 1 });
    };

    const enableNextStepInDescribeStore = () => {
        dispatch({ type: 'SET_DESCRIBE_STORE_STAGE_READY', isDescribeStoreStageReady: true });
    };

    const handlePluginsSelected = (plugins) => {
        dispatch({ type: 'SET_SELECTED_PLUGINS', selectedPlugins: plugins });
        updatePreset({plugins});
    };

    const handleFixturesGenerated = (fixtures) => {
        updatePreset({fixtures});
    };

    const resetWizard = useCallback(() => {
        // localStorage.clear(); // No need, useWizardState persists state
        dispatch({ type: 'RESET_WIZARD' });
        navigate(`/wizard/${stepPaths[0]}`, {replace: true});
        // Reload page to ensure complete reset
        window.location.reload();
    }, [navigate, dispatch]);

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
                        onClick={resetWizard}
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
                            onClick={() => dispatch({ type: 'SET_ERROR', error: null })}
                            className="text-red-500 hover:text-red-700 text-xs underline mt-1"
                        >
                            Dismiss
                        </button>
                    </div>
                )}
            </div>
            <div className="flex-1 min-h-0 flex flex-col">
                <div className="flex-1 overflow-y-auto min-h-0 relative">
                    <AnimatePresence custom={wiz.direction} initial={false} mode="wait" presenceAffectsLayout={false}>
                        {/* Step 1: Plugins */}
                        {wiz.step === 1 && (
                            <motion.div
                                key="1"
                                custom={wiz.direction}
                                variants={stepVariants}
                                initial="enter"
                                animate="center"
                                exit="exit"
                                transition={{duration: 0.25, type: 'tween', ease: 'easeInOut'}}
                            >
                                <div className="flex flex-col items-center justify-start w-full pt-6"
                                     style={{minHeight: '60vh'}}>
                                    <div className="w-full max-w-lg">
                                        {pluginsLoading ? (
                                            <div className="flex flex-col items-center justify-center py-8">
                                                <div
                                                    className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mb-4"></div>
                                                <p className="text-gray-600">Loading plugins...</p>
                                            </div>
                                        ) : pluginsError ? (
                                            <div className="text-red-600 mb-4 p-4 bg-red-50 rounded-lg">
                                                <p className="font-medium">Failed to load plugins:</p>
                                                <p className="text-sm">{pluginsError}</p>
                                                <button
                                                    onClick={() => refetch()}
                                                    className="mt-2 text-blue-600 underline text-sm hover:text-blue-800"
                                                >
                                                    Try again
                                                </button>
                                            </div>
                                        ) : (
                                            <>
                                                <div className="grid grid-cols-1 gap-2 mb-6 overflow-y-auto"
                                                     style={{maxHeight: 360}}>
                                                    {plugins.map(p => (
                                                        <label key={p.composer} className="flex items-center space-x-2">
                                                            <input
                                                                type="checkbox"
                                                                value={p.composer}
                                                                checked={wiz.selectedPlugins.includes(p.composer)}
                                                                onChange={e => {
                                                                    const c = e.target.value;
                                                                    let updated;
                                                                    if (wiz.selectedPlugins.includes(c)) {
                                                                        updated = wiz.selectedPlugins.filter(x => x !== c);
                                                                    } else {
                                                                        updated = [...wiz.selectedPlugins, c];
                                                                    }
                                                                    handlePluginsSelected(updated);
                                                                }}
                                                                className="h-4 w-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
                                                            />
                                                            <span className="text-gray-800 text-sm">
                                                                {PLUGIN_LABELS[p.name.replace(/^sylius\//, '')] || prettify(p.name)} ({p.version})
                                                            </span>
                                                        </label>
                                                    ))}
                                                </div>
                                                <button
                                                    onClick={handleNext}
                                                    disabled={pluginsLoading}
                                                    className={`w-full py-2 rounded-lg font-medium transition ${
                                                        pluginsLoading
                                                            ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                                            : 'bg-teal-600 hover:bg-teal-700 text-white'
                                                    }`}
                                                >
                                                    {pluginsLoading ? 'Loading plugins...' : 'Next →'}
                                                </button>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </motion.div>
                        )}
                        {/* Step 2: Fixtures (DescribeStoreStage) with side panel */}
                        {wiz.step === 2 && (
                            <motion.div
                                key="2"
                                custom={wiz.direction}
                                variants={stepVariants}
                                initial="enter"
                                animate="center"
                                exit="exit"
                                transition={{duration: 0.25, type: 'tween', ease: 'easeInOut'}}
                            >
                                <div className="flex flex-row w-full min-h-[70vh] gap-6">
                                    <div className="flex-1 flex flex-col min-h-0">
                                        <DescribeStoreStage
                                            onReadyToProceed={() => dispatch({ type: 'SET_DESCRIBE_STORE_STAGE_READY', isDescribeStoreStageReady: true })}
                                            onStoreDetailsChange={details => dispatch({ type: 'SET_STORE_DETAILS', storeDetails: details })}
                                            presetId={presetId}
                                            updatePreset={updatePreset}
                                            onNext={handleNext}
                                        />
                                    </div>
                                </div>
                                <div className="flex justify-between mt-6">
                                    <button onClick={back}
                                            className="text-teal-600 hover:underline rounded-lg px-4 py-2">←
                                        Back
                                    </button>
                                </div>
                            </motion.div>
                        )}
                        {/* Step 3: Download store-preset zip after generation */}
                        {wiz.step === 3 && (
                            <motion.div
                                key="3"
                                custom={wiz.direction}
                                variants={stepVariants}
                                initial="enter"
                                animate="center"
                                exit="exit"
                                transition={{duration: 0.25, type: 'tween', ease: 'easeInOut'}}
                            >
                                <GenerateStorePresetSection
                                    onReady={() => dispatch({ type: 'SET_FIXTURES_READY', isFixturesReady: true })}
                                    onGenerating={(generating) => dispatch({ type: 'SET_FIXTURES_GENERATING', isFixturesGenerating: generating })}
                                    storeDetails={wiz.storeDetails}
                                    presetId={presetId}
                                />
                                <div className="flex justify-between mt-6">
                                    <button onClick={back}
                                            className="text-teal-600 hover:underline rounded-lg px-4 py-2">← Back
                                    </button>
                                    <button
                                        onClick={handleNext}
                                        disabled={!wiz.isFixturesReady}
                                        className={`py-2 px-4 rounded-md font-medium transition ${
                                            wiz.isFixturesReady ? 'bg-teal-600 hover:bg-teal-700 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                        }`}
                                    >Next →
                                    </button>
                                </div>
                            </motion.div>
                        )}
                        {/* Step 4: Deploy Target */}
                        {wiz.step === 4 && (
                            <motion.div
                                key="5"
                                custom={wiz.direction}
                                variants={stepVariants}
                                initial="enter"
                                animate="center"
                                exit="exit"
                                transition={{duration: 0.25, type: 'tween', ease: 'easeInOut'}}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-teal-700">5. Summary & Deploy</h2>
                                <div className="mb-4">
                                    <h3 className="font-semibold text-gray-800">Plugins:</h3>
                                    <ul className="list-disc list-inside text-sm text-gray-700">
                                        {wiz.selectedPlugins.map(c => {
                                            const p = plugins.find(x => x.composer === c);
                                            return <li
                                                key={c}>{PLUGIN_LABELS[p?.name?.replace(/^sylius\//, '')] || prettify(p?.name)} ({p?.version})</li>;
                                        })}
                                    </ul>
                                </div>
                                <div className="mb-4">
                                    <h3 className="font-semibold text-gray-800">Deploy:</h3>
                                    <p className="text-sm text-gray-700">{wiz.target}{wiz.target === 'platform.sh' && wiz.env ? ` (${wiz.env})` : ''}</p>
                                </div>
                                <div className="flex justify-center">
                                    <button
                                        disabled={wiz.deployStatus !== 'complete'}
                                        onClick={() => window.open(wiz.deployUrl, '_blank')}
                                        className={`py-2 px-4 rounded-lg font-medium transition ${
                                            wiz.deployStatus === 'complete'
                                                ? 'bg-green-600 hover:bg-green-700 text-white'
                                                : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200'
                                        } flex items-center space-x-2 mx-auto`}
                                    >
                                        {wiz.deployStatus === 'in_progress' && (
                                            <svg className="animate-spin h-5 w-5 text-white"
                                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor"
                                                        strokeWidth="4"/>
                                                <path className="opacity-75" fill="currentColor"
                                                      d="M4 12a8 8 0 018-8v8z"/>
                                            </svg>
                                        )}
                                        <span>
                                            {wiz.deployStatus === 'in_progress' && 'Deploying...'}
                                            {wiz.deployStatus === 'complete' && 'Go to demo'}
                                            {wiz.deployStatus === 'failed' && 'Deploy failed'}
                                        </span>
                                    </button>
                                </div>
                            </motion.div>
                        )}
                    </AnimatePresence>
                </div>
            </div>
        </motion.div>
    );
}

function GenerateStorePresetSection({
                                        onReady,
                                        onGenerating,
                                        storeDetails,
                                        presetId
                                    }) {
    const [isFixturesReady, setIsFixturesReady] = useState(false);
    const [isFixturesGenerating, setIsFixturesGenerating] = useState(false);
    const [fixturesError, setFixturesError] = useState(null);
    const [timedOut, setTimedOut] = useState(false);
    const timeoutRef = React.useRef();

    // Nowe stany dla generowania obrazów
    const [isImagesReady, setIsImagesReady] = useState(false);
    const [isImagesGenerating, setIsImagesGenerating] = useState(false);
    const [imagesError, setImagesError] = useState(null);

    const conversation = useConversation();
    const {handleCreateFixtures} = conversation;

    // Automatyczne generowanie obrazów po fixtures
    useEffect(() => {
        if (isFixturesReady && !isImagesReady && !isImagesGenerating && !imagesError) {
            handleGenerateImages();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isFixturesReady]);

    // Notify parent when ready (teraz tylko gdy obrazy są gotowe)
    useEffect(() => {
        if (isFixturesReady && isImagesReady) {
            onReady();
        }
    }, [isFixturesReady, isImagesReady, onReady]);

    // Notify parent about generating state (fixtures + images)
    useEffect(() => {
        onGenerating(isFixturesGenerating || isImagesGenerating);
    }, [isFixturesGenerating, isImagesGenerating, onGenerating]);

    const handleGenerateFixtures = () => {
        setIsFixturesGenerating(true);
        setFixturesError(null);
        setTimedOut(false);
        setIsImagesReady(false);
        setIsImagesGenerating(false);
        setImagesError(null);
        timeoutRef.current = setTimeout(() => {
            setTimedOut(true);
            setIsFixturesGenerating(false);
            setFixturesError('Timeout: Store preset generation took too long. Please try again.');
        }, 120000);
        handleCreateFixtures(presetId, storeDetails)
            .then(() => {
                clearTimeout(timeoutRef.current);
                setIsFixturesReady(true);
            })
            .catch((err) => {
                clearTimeout(timeoutRef.current);
                setFixturesError(err?.message || 'Unknown error');
                setIsFixturesReady(false);
            })
            .finally(() => {
                setIsFixturesGenerating(false);
            });
    };

    const handleGenerateImages = async () => {
        setIsImagesGenerating(true);
        setImagesError(null);
        try {
            if (!presetId) throw new Error('Brak presetId!');
            // Tu w przyszłości można dodać polling statusu generowania obrazów
            const res = await fetch(`/api/store-presets/${encodeURIComponent(presetId)}/generate-images`, {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({}),
            });
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.error || data.message || 'Failed to generate images');
            }
            setIsImagesReady(true);
        } catch (err) {
            setImagesError(err?.message || 'Unknown error');
            setIsImagesReady(false);
        } finally {
            setIsImagesGenerating(false);
        }
    };

    const handleRetry = () => {
        setFixturesError(null);
        setTimedOut(false);
        setIsFixturesReady(false);
        setIsImagesReady(false);
        setIsImagesGenerating(false);
        setImagesError(null);
    };

    const handleRetryImages = () => {
        setImagesError(null);
        setIsImagesReady(false);
    };

    const handleReset = () => {
        setFixturesError(null);
        setTimedOut(false);
        setIsFixturesReady(false);
        setIsFixturesGenerating(false);
        setIsImagesReady(false);
        setIsImagesGenerating(false);
        setImagesError(null);
    };

    // DEBUG: ręczne wywołanie PATCH /api/store-presets/{presetId}/fixtures
    const [debugResult, setDebugResult] = useState(null);
    const [debugLoading, setDebugLoading] = useState(false);
    const handleDebugCreateFixtures = async () => {
        setDebugLoading(true);
        setDebugResult(null);
        try {
            if (!presetId) throw new Error('Brak presetId!');
            const payload = {
                storeDetails: storeDetails || {},
                debug: true,
            };
            const res = await fetch(`/api/store-presets/${encodeURIComponent(presetId)}/fixtures-generate`, {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
            });
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                data = text;
            }
            if (!res.ok) {
                setDebugResult({error: data.error || data || 'Unknown error', status: res.status});
            } else {
                setDebugResult({success: data});
            }
        } catch (e) {
            setDebugResult({error: e.message});
        } finally {
            setDebugLoading(false);
        }
    };

    return (
        <div className="flex flex-col items-center justify-center gap-4">
            {/* Step 1: Generate Fixtures */}
            {!isFixturesReady && !isFixturesGenerating && !fixturesError && (
                <>
                    <div className="text-center">
                        <p className="text-gray-600 mb-4">Ready to generate your store preset</p>
                        <button
                            onClick={handleGenerateFixtures}
                            disabled={!storeDetails}
                            className={`py-3 px-8 rounded-lg font-medium shadow transition ${
                                storeDetails
                                    ? 'bg-teal-600 hover:bg-teal-700 text-white'
                                    : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                            }`}
                        >
                            {storeDetails ? 'Generate Fixtures' : 'Complete store description first'}
                        </button>
                    </div>
                </>
            )}
            {/* Step 1: Fixtures Generating */}
            {isFixturesGenerating && (
                <>
                    <div className="mb-4 text-center">Crafting your perfect store...</div>
                    <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-teal-600"></div>
                </>
            )}
            {/* Step 1: Fixtures Error */}
            {fixturesError && !isFixturesGenerating && (
                <>
                    <div className="text-red-600 mb-2 text-center">{fixturesError}</div>
                    <button
                        onClick={handleRetry}
                        className="py-2 px-6 bg-teal-600 hover:bg-teal-700 text-white rounded-md font-medium shadow transition"
                    >
                        Retry
                    </button>
                </>
            )}
            {/* Step 2: Images Generating lub czekanie na zakończenie */}
            {isFixturesReady && (!isImagesReady || isImagesGenerating) && !imagesError && (
                <>
                    <div className="mb-4 text-center">Generating beautiful product images...</div>
                    <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-600"></div>
                </>
            )}
            {/* Step 2: Images Error */}
            {imagesError && !isImagesGenerating && (
                <>
                    <div className="text-red-600 mb-2 text-center">{imagesError}</div>
                    <button
                        onClick={handleRetryImages}
                        className="py-2 px-6 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium shadow transition"
                    >
                        Retry Images
                    </button>
                </>
            )}
            {/* Step 3: Summary (bez pobierania ZIP) */}
            {isFixturesReady && isImagesReady && !fixturesError && !imagesError && !isFixturesGenerating && !isImagesGenerating && (
                <div className="text-center">
                    <div className="mb-4">
                        <div
                            className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p className="text-green-600 font-medium">Store preset ready!</p>
                        <p className="text-gray-600 text-sm">Fixtures and images generated successfully</p>
                        {/* Tu można dodać podsumowanie lub status, bez przycisku pobierania */}
                    </div>
                </div>
            )}
            <div className="mt-4">
                <button
                    type="button"
                    onClick={handleDebugCreateFixtures}
                    className="py-2 px-4 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md text-xs font-mono border border-gray-300"
                    disabled={debugLoading}
                >
                    {debugLoading ? 'Debugging...' : 'Debug: Wywołaj /api/store-presets/{presetId}/fixtures'}
                </button>
                {debugResult && (
                    <div className="mt-2 text-xs text-left break-all max-w-md">
                        <pre className={debugResult.error ? 'text-red-600' : 'text-green-700'}
                             style={{whiteSpace: 'pre-wrap', wordBreak: 'break-all'}}>
                            {debugResult.error ? `Błąd: ${debugResult.error} (status: ${debugResult.status || ''})` : JSON.stringify(debugResult.success, null, 2)}
                        </pre>
                    </div>
                )}
            </div>
        </div>
    );
}
