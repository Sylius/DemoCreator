import React, {useState, useEffect, useCallback} from 'react';
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
    'Upload your logo',
    'Choose deployment target',
    'Summary',
];

const stepDescriptions = [
    'Plugins are optional. You can select any to include, or proceed without plugins.',
    'Provide a description of your store and its details.',
    'Generating your store, please wait...',
    'Upload a logo for your demo store.',
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
    const [step, setStep] = useState(initialStepIndex + 1);
    const [direction, setDirection] = useState(1); // 1 = handleNext, -1 = back
    const {plugins, loading: pluginsLoading, error: pluginsError, refetch} = useSupportedPlugins();
    const [targets, setTargets] = useState([]);
    const [selectedPlugins, setSelectedPlugins] = useState(() => {
        const stored = localStorage.getItem('selectedPlugins');
        return stored ? JSON.parse(stored) : [];
    });
    const [logoFile, setLogoFile] = useState(null);
    const [logoUrl, setLogoUrl] = useState(null);
    const [target, setTarget] = useState('');
    const [envOptions, setEnvOptions] = useState([]);
    const [env, setEnv] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [deployStateId, setDeployStateId] = useState(null);
    const [deployUrl, setDeployUrl] = useState(null);
    const [deployStatus, setDeployStatus] = useState(null);
    const [storeDetails, setStoreDetails] = useState(null);
    const [describeStoreStage, setDescribeStoreStage] = useState(null);
    const [isDescribeStoreStageReady, setIsDescribeStoreStageReady] = useState(false);
    const [isFixturesReady, setIsFixturesReady] = useState(false);
    const [isFixturesGenerating, setIsFixturesGenerating] = useState(false);
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

    // Po załadowaniu komponentu spróbuj załadować storeDetails z localStorage jeśli są dostępne
    useEffect(() => {
        if (storeDetails == null) {
            try {
                const stored = localStorage.getItem('storeDetails');
                if (stored) {
                    setStoreDetails(JSON.parse(stored));
                }
            } catch (e) {
                // ignore
            }
        }
    }, []);

    // Synchronize step with URL with delay to allow animation
    useEffect(() => {
        console.log(describeStoreStage);
        if (!stepParam || stepParam !== stepPaths[step - 1]) {
            const timer = setTimeout(() => {
                navigate(`/wizard/${stepPaths[step - 1]}`, {replace: true});
            }, 100); // Small delay to allow animation to start
            return () => clearTimeout(timer);
        }
    }, [step, stepParam, navigate]);

    // Redirect /wizard to first step
    useEffect(() => {
        if (!stepParam) {
            navigate(`/wizard/${stepPaths[0]}`, {replace: true});
        }
    }, [stepParam, navigate]);

    useEffect(() => {
        if (step === 4 && target === 'platform.sh') {
            fetch(environmentsUrl)
                .then(r => r.json())
                .then(data => setEnvOptions(data.environments || []))
                .catch(() => setError('Failed to fetch environments'));
        }
    }, [step, target, environmentsUrl]);

    useEffect(() => {
        if (step === 5 && deployStateId) {
            const interval = setInterval(() => {
                fetch(`${deployStateUrlBase}/${env}/${deployStateId}`)
                    .then(r => r.json())
                    .then(data => {
                        setDeployStatus(data.status);
                        if (data.status !== 'in_progress') {
                            clearInterval(interval);
                        }
                    })
                    .catch(() => {
                        setError('Failed to check deploy status');
                        clearInterval(interval);
                    });
            }, 20000);
            return () => clearInterval(interval);
        }
    }, [step, deployStateId, env, deployStateUrlBase]);

    // Reset describeStoreStageReady when step changes away from 2
    useEffect(() => {
        if (step !== 2 && isDescribeStoreStageReady) {
            setIsDescribeStoreStageReady(false);
        }
    }, [step, isDescribeStoreStageReady]);

    // Persist selected plugins to localStorage
    useEffect(() => {
        localStorage.setItem('selectedPlugins', JSON.stringify(selectedPlugins));
    }, [selectedPlugins]);

    // Reset fixtures state when entering step 3 (preview-store)
    useEffect(() => {
        if (step === 3) {
            // Reset fixtures state to allow regeneration
            setIsFixturesReady(false);
            setIsFixturesGenerating(false);
        }
    }, [step]);

    const handleNext = useCallback(() => {
        try {
            // Jeśli jesteśmy na kroku 1 (pluginy), wyślij PATCH z wybranymi pluginami
            if (step === 1) {
                if (pluginsLoading) {
                    setError('Please wait for plugins to load');
                    return;
                }
                if (pluginsError) {
                    setError('Please fix plugin loading error before proceeding');
                    return;
                }

                // Konwertuj wybrane pluginy do formatu { "sylius/plugin-name": "^1.0" }
                const pluginsPayload = {};
                selectedPlugins.forEach(pluginName => {
                    const plugin = plugins.find(p => p.composer === pluginName);
                    if (plugin) {
                        pluginsPayload[plugin.name] = `^${plugin.version}`;
                    }
                });
                updatePreset({plugins: pluginsPayload});
            }

            // Sprawdź czy można przejść dalej
            if (step === 2 && !isDescribeStoreStageReady) {
                setError('Please complete the store description before proceeding');
                return;
            }

            setError(null); // Clear any previous errors
            setDirection(1);
            // Use setTimeout to ensure direction is set before step change
            setTimeout(() => {
                setStep(s => Math.min(s + 1, stepPaths.length));
            }, 0);
        } catch (err) {
            setError(`Failed to proceed: ${err.message}`);
        }
    }, [step, pluginsLoading, pluginsError, selectedPlugins, plugins, updatePreset, isDescribeStoreStageReady, setError, setDirection, setStep]);
    const back = useCallback(() => {
        setDirection(-1);
        // Use setTimeout to ensure direction is set before step change
        setTimeout(() => {
            setStep(s => Math.max(s - 1, 1));
        }, 0);
    }, [setDirection, setStep]);

    const uploadLogo = async () => {
        if (!logoFile) return;
        const form = new FormData();
        form.append('logo', logoFile);
        const res = await fetch(logoUploadUrl, {method: 'POST', body: form});
        const data = await res.json();
        if (!res.ok) throw new Error(data.error);
        setLogoUrl(data.logoUrl);
    };

    const buildPluginPayload = () => {
        const map = {};
        selectedPlugins.forEach(composer => {
            const plugin = plugins.find(p => p.composer === composer);
            if (plugin) map[composer] = plugin.version;
        });
        return map;
    };

    const handleDeploy = async () => {
        setLoading(true);
        setError(null);
        try {
            if (logoFile) await uploadLogo();
            const payload = {
                environment: target === 'platform.sh' ? env : undefined,
                plugins: buildPluginPayload(),
                logoUrl,
                target,
            };
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message);
            setDeployStateId(data.deployStateId);
            setDeployUrl(data.url);
            setDeployStatus('in_progress');
            setDirection(1);
            setTimeout(() => {
                setStep(5);
            }, 0);
        } catch (e) {
            setError(e.message);
        } finally {
            setLoading(false);
        }
    };

    const handleStoreDetailsConfirm = () => {
        setDirection(1);
        setTimeout(() => {
            setStep(s => Math.min(s + 1, stepPaths.length));
        }, 0);
    };

    const enableNextStepInDescribeStore = () => {
        setIsDescribeStoreStageReady(true);
    };

    const handlePluginsSelected = (plugins) => {
        setSelectedPlugins(plugins);
        updatePreset({plugins});
    };

    const handleFixturesGenerated = (fixtures) => {
        updatePreset({fixtures});
    };

    const resetWizard = useCallback(() => {
        localStorage.clear(); // czyści wszystko
        setSelectedPlugins([]);
        setLogoFile(null);
        setLogoUrl(null);
        setTarget('');
        setEnv('');
        setStoreDetails(null);
        setIsDescribeStoreStageReady(false);
        setIsFixturesGenerating(false);
        setIsFixturesReady(false);
        setDeployStateId(null);
        setDeployUrl(null);
        setDeployStatus(null);
        setError(null);
        setLoading(false);
        setDirection(1);
        setStep(1);
        navigate(`/wizard/${stepPaths[0]}`, {replace: true});
        // Reload page to ensure complete reset
        window.location.reload();
    }, [navigate]);

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
                                  className={`h-2 w-2 rounded-full transition-colors duration-200 ${step === idx + 1 ? 'bg-teal-600' : 'bg-gray-200'}`}></span>
                        ))}
                    </div>
                    <div className="text-sm text-gray-500">
                        {presetId && `ID: ${presetId.slice(-8)}`}
                    </div>
                </div>
                <h2 className="text-2xl font-bold text-center mt-6 mb-2">{stepTitles[step - 1]}</h2>
                {stepDescriptions[step - 1] && (
                    <p className="text-gray-500 text-center mb-4">{stepDescriptions[step - 1]}</p>
                )}
                {error && (
                    <div className="mx-4 mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p className="text-red-700 text-sm">{error}</p>
                        <button
                            onClick={() => setError(null)}
                            className="text-red-500 hover:text-red-700 text-xs underline mt-1"
                        >
                            Dismiss
                        </button>
                    </div>
                )}
            </div>
            <div className="flex-1 min-h-0 flex flex-col">
                <div className="flex-1 overflow-y-auto min-h-0 relative">
                    <AnimatePresence custom={direction} initial={false} mode="wait" presenceAffectsLayout={false}>
                        {/* Step 1: Plugins */}
                        {step === 1 && (
                            <motion.div
                                key="1"
                                custom={direction}
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
                                                                checked={selectedPlugins.includes(p.composer)}
                                                                onChange={e => {
                                                                    const c = e.target.value;
                                                                    setSelectedPlugins(sel => sel.includes(c) ? sel.filter(x => x !== c) : [...sel, c]);
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
                        {step === 2 && (
                            <motion.div
                                key="2"
                                custom={direction}
                                variants={stepVariants}
                                initial="enter"
                                animate="center"
                                exit="exit"
                                transition={{duration: 0.25, type: 'tween', ease: 'easeInOut'}}
                            >
                                <div className="flex flex-row w-full min-h-[70vh] gap-6">
                                    <div className="flex-1 flex flex-col min-h-0">
                                        <DescribeStoreStage
                                            onReadyToProceed={() => setIsDescribeStoreStageReady(true)}
                                            onStoreDetailsChange={setStoreDetails}
                                            presetId={presetId}
                                            updatePreset={updatePreset}
                                            onNext={handleNext}
                                            isReady={isDescribeStoreStageReady}
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
                        {step === 3 && (
                            <motion.div
                                key="3"
                                custom={direction}
                                variants={stepVariants}
                                initial="enter"
                                animate="center"
                                exit="exit"
                                transition={{duration: 0.25, type: 'tween', ease: 'easeInOut'}}
                            >
                                <GenerateStorePresetSection
                                    onReady={() => setIsFixturesReady(true)}
                                    onGenerating={(generating) => setIsFixturesGenerating(generating)}
                                    storeDetails={storeDetails}
                                    presetId={presetId}
                                />
                                <div className="flex justify-between mt-6">
                                    <button onClick={back}
                                            className="text-teal-600 hover:underline rounded-lg px-4 py-2">← Back
                                    </button>
                                    <button
                                        onClick={handleNext}
                                        disabled={!isFixturesReady}
                                        className={`py-2 px-4 rounded-md font-medium transition ${
                                            isFixturesReady ? 'bg-teal-600 hover:bg-teal-700 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                        }`}
                                    >Next →
                                    </button>
                                </div>
                            </motion.div>
                        )}
                        {/* Step 4: Logo Upload */}
                        {step === 4 && (
                            <motion.div
                                key="4"
                                custom={direction}
                                variants={stepVariants}
                                initial="enter"
                                animate="center"
                                exit="exit"
                                transition={{duration: 0.25, type: 'tween', ease: 'easeInOut'}}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-teal-700">4. Logo</h2>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={e => {
                                        const file = e.target.files[0];
                                        setLogoFile(file);
                                        setLogoUrl(file ? URL.createObjectURL(file) : null);
                                    }}
                                    className="w-full mb-4 text-sm text-gray-700"
                                />
                                {logoUrl &&
                                    <img src={logoUrl} alt="Logo" className="h-16 object-contain mx-auto mb-4"/>}
                                <div className="flex justify-between">
                                    <button onClick={back}
                                            className="text-teal-600 hover:underline rounded-lg px-4 py-2">←
                                        Back
                                    </button>
                                    <button onClick={handleNext}
                                            className="py-2 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-medium shadow transition">Next
                                        →
                                    </button>
                                </div>
                            </motion.div>
                        )}
                        {/* Step 5: Deploy Target */}
                        {step === 5 && (
                            <motion.div
                                key="5"
                                custom={direction}
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
                                        {selectedPlugins.map(c => {
                                            const p = plugins.find(x => x.composer === c);
                                            return <li
                                                key={c}>{PLUGIN_LABELS[p?.name?.replace(/^sylius\//, '')] || prettify(p?.name)} ({p?.version})</li>;
                                        })}
                                    </ul>
                                </div>
                                <div className="mb-4">
                                    <h3 className="font-semibold text-gray-800">Deploy:</h3>
                                    <p className="text-sm text-gray-700">{target}{target === 'platform.sh' && env ? ` (${env})` : ''}</p>
                                </div>
                                <div className="flex justify-center">
                                    <button
                                        disabled={deployStatus !== 'complete'}
                                        onClick={() => window.open(deployUrl, '_blank')}
                                        className={`py-2 px-4 rounded-lg font-medium transition ${
                                            deployStatus === 'complete'
                                                ? 'bg-green-600 hover:bg-green-700 text-white'
                                                : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200'
                                        } flex items-center space-x-2 mx-auto`}
                                    >
                                        {deployStatus === 'in_progress' && (
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
                                                {deployStatus === 'in_progress' && 'Deploying...'}
                                            {deployStatus === 'complete' && 'Go to demo'}
                                            {deployStatus === 'failed' && 'Deploy failed'}
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

function DownloadStorePresetButton({storePresetName}) {
    const [downloadError, setDownloadError] = useState(null);
    const [downloading, setDownloading] = useState(false);

    const isValidPresetName = typeof storePresetName === 'string' && storePresetName.length > 0 && storePresetName !== '[object Object]';

    const handleDownload = async (e) => {
        e.preventDefault();
        setDownloadError(null);
        setDownloading(true);
        try {
            if (!isValidPresetName) {
                throw new Error('Invalid preset ID. Cannot download.');
            }
            const url = `/api/download-store-preset/${encodeURIComponent(storePresetName)}`;
            const res = await fetch(url);
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.error || `Download failed: ${res.status} ${res.statusText}`);
            }
            const blob = await res.blob();
            const a = document.createElement('a');
            const downloadUrl = window.URL.createObjectURL(blob);
            a.href = downloadUrl;
            a.download = `${storePresetName}.zip`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(downloadUrl);
        } catch (err) {
            setDownloadError(err.message || 'Download failed');
        } finally {
            setDownloading(false);
        }
    };

    return (
        <div className="flex flex-col items-center gap-2">
            <button
                onClick={handleDownload}
                className="py-2 px-6 bg-teal-600 hover:bg-teal-700 text-white rounded-md font-medium shadow transition"
                disabled={downloading || !isValidPresetName}
            >
                {downloading ? 'Downloading...' : 'Download ZIP'}
            </button>
            {!isValidPresetName && (
                <div className="text-red-600 text-sm mt-2">Invalid or missing preset ID. Cannot download.</div>
            )}
            {downloadError && (
                <div className="text-red-600 text-sm mt-2">{downloadError}</div>
            )}
        </div>
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
