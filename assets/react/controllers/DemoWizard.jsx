import React, {useState, useEffect} from 'react';
import {motion, AnimatePresence} from 'framer-motion';
import { useNavigate, useParams } from 'react-router-dom';
import DescribeStoreStage from './DescribeStoreStage';
import {useSupportedPlugins} from '../hooks/useSupportedPlugins';
import { useConversation } from './DescribeStoreStage/hooks/useConversation';

const stepVariants = {
    enter: (direction) => ({
        opacity: 0,
        x: direction > 0 ? 100 : -100,
        position: 'absolute',
        width: '100%'
    }),
    center: {
        opacity: 1,
        x: 0,
        position: 'relative',
        width: '100%'
    },
    exit: (direction) => ({
        opacity: 0,
        x: direction > 0 ? -100 : 100,
        position: 'absolute',
        width: '100%'
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
    'preview-store',
    'upload-logo',
    'choose-deploy',
    'summary'
];

const stepTitles = [
    'Choose plugins',
    'Describe your store',
    'Preview your store',
    'Upload your logo',
    'Choose deployment target',
    'Summary',
];
const stepDescriptions = [
    'Select the plugins you want to include in your demo store.',
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
    const {plugins, loading: pluginsLoading, error: pluginsError} = useSupportedPlugins();
    const [targets, setTargets] = useState([]);
    const [selectedPlugins, setSelectedPlugins] = useState([]);
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
    const [storeDetailsPanelState, setStoreDetailsPanelState] = useState(null); // for storing storeDetails
    const [describeStoreStage, setDescribeStoreStage] = useState(null);
    const [isDescribeStoreStageReady, setIsDescribeStoreStageReady] = useState(false);
    const [isFixturesGenerating, setIsFixturesGenerating] = useState(false);
    const [fixturesError, setFixturesError] = useState(null);
    const [isFixturesReady, setIsFixturesReady] = useState(false);
    const conversation = useConversation();
    const { handleCreateFixtures } = conversation;
    const suiteName = conversation?.storeDetails?.suiteName || 'preset';

    // Synchronize step with URL
    useEffect(() => {
        console.log(describeStoreStage);
        if (!stepParam || stepParam !== stepPaths[step - 1]) {
            navigate(`/wizard/${stepPaths[step - 1]}`, {replace: true});
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

    const handleNext = () => {
        setDirection(1);
        setStep(s => Math.min(s + 1, stepPaths.length));
    };
    const back = () => {
        setDirection(-1);
        setStep(s => Math.max(s - 1, 1));
    };

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
            setStep(5);
        } catch (e) {
            setError(e.message);
        } finally {
            setLoading(false);
        }
    };

    const handleStoreDetailsConfirm = () => {
        setStep(s => Math.min(s + 1, stepPaths.length));
    };

    const enableNextStepInDescribeStore = () => {
        setIsDescribeStoreStageReady(true);
    };

    return (
        <motion.div
            initial={{ opacity: 0, scale: 0.98, y: 30 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            transition={{ duration: 0.5, ease: 'easeOut' }}
            style={{padding: 0}}
        >
            <div className="mb-0 sticky top-0 z-10">
                <div className="flex items-center gap-2 mt-4 justify-center">
                    {steps.map((label, idx) => (
                        <span key={label}
                              className={`h-2 w-2 rounded-full transition-colors duration-200 ${step === idx + 1 ? 'bg-teal-600' : 'bg-gray-200'}`}></span>
                    ))}
                </div>
                <h2 className="text-2xl font-bold text-center mt-6 mb-2">{stepTitles[step - 1]}</h2>
                {stepDescriptions[step - 1] && (
                    <p className="text-gray-500 text-center mb-4">{stepDescriptions[step - 1]}</p>
                )}
            </div>
            <div className="flex-1 min-h-0 flex flex-col">
                <div className="flex-1 overflow-y-auto min-h-0">
                    <AnimatePresence custom={direction} initial={false} mode="wait">
                        {/* Step 1: Plugins */}
                        {step === 1 && (
                            <motion.div
                                key="1"
                                custom={direction}
                                variants={stepVariants}
                                initial="enter"
                                animate="center"
                                exit="exit"
                                transition={{duration: 0.35, type: 'tween'}}
                            >
                                <div className="flex flex-col items-center justify-start w-full pt-6"
                                     style={{minHeight: '60vh'}}>
                                    <div className="w-full max-w-lg">
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
                                            disabled={!selectedPlugins.length}
                                            className={`w-full py-2 rounded-lg font-medium transition ${
                                                selectedPlugins.length ? 'bg-teal-600 hover:bg-teal-700 text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200'
                                            }`}
                                        >Next →
                                        </button>
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
                                transition={{duration: 0.35, type: 'tween'}}
                            >
                                <div className="flex flex-row w-full min-h-[70vh] gap-6">
                                    <div className="flex-1 flex flex-col min-h-0">
                                        <DescribeStoreStage onReadyToProceed={() => setIsDescribeStoreStageReady(true)}/>
                                    </div>
                                </div>
                                <div className="flex justify-between mt-6">
                                    <button onClick={back} className="text-teal-600 hover:underline rounded-lg px-4 py-2">←
                                        Back
                                    </button>
                                    <button
                                        onClick={handleNext}
                                        disabled={!isDescribeStoreStageReady}
                                        className={`py-2 px-4 rounded-lg font-medium transition ${
                                            isDescribeStoreStageReady ? 'bg-teal-600 hover:bg-teal-700 text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200'
                                        }`}
                                    >Next →
                                    </button>
                                </div>
                            </motion.div>
                        )}
                        {/* Step 3: Download store-preset zip after generation */}
                        {step === 3 && (
                            <motion.div key="3" variants={stepVariants} initial="hidden" animate="visible" exit="exit" transition={{duration: 0.3}}>
                                <h2 className="text-lg font-medium mb-4 text-teal-700">3. Download your store preset</h2>
                                <GenerateStorePresetSection
                                    isFixturesReady={isFixturesReady}
                                    fixturesError={fixturesError}
                                    setFixturesError={setFixturesError}
                                    setIsFixturesReady={setIsFixturesReady}
                                    setIsFixturesGenerating={setIsFixturesGenerating}
                                    isFixturesGenerating={isFixturesGenerating}
                                    handleCreateFixtures={handleCreateFixtures}
                                    suiteName={suiteName}
                                />
                                <div className="flex justify-between mt-6">
                                    <button onClick={back} className="text-teal-600 hover:underline">← Back</button>
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
                                transition={{duration: 0.35, type: 'tween'}}
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
                                    <button onClick={back} className="text-teal-600 hover:underline rounded-lg px-4 py-2">←
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
                                transition={{duration: 0.35, type: 'tween'}}
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
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                        strokeWidth="4"/>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
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

function DownloadStorePresetButton({ suiteName }) {
    const [downloadError, setDownloadError] = useState(null);
    const [downloading, setDownloading] = useState(false);

    const handleDownload = async (e) => {
        e.preventDefault();
        setDownloadError(null);
        setDownloading(true);
        try {
            const url = `/api/download-store-preset/${encodeURIComponent(suiteName)}`;
            const res = await fetch(url);
            if (!res.ok) {
                throw new Error(`Download failed: ${res.status} ${res.statusText}`);
            }
            const blob = await res.blob();
            const a = document.createElement('a');
            const downloadUrl = window.URL.createObjectURL(blob);
            a.href = downloadUrl;
            a.download = `${suiteName}.zip`;
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
                disabled={downloading}
            >
                {downloading ? 'Downloading...' : 'Download ZIP'}
            </button>
            {downloadError && (
                <div className="text-red-600 text-sm mt-2">{downloadError}</div>
            )}
        </div>
    );
}

function GenerateStorePresetSection({
    isFixturesReady,
    fixturesError,
    setFixturesError,
    setIsFixturesReady,
    setIsFixturesGenerating,
    isFixturesGenerating,
    handleCreateFixtures,
    suiteName
}) {
    const [hasTried, setHasTried] = useState(false);
    const [timedOut, setTimedOut] = useState(false);
    const timeoutRef = React.useRef();

    // Try to generate fixtures on mount or retry
    useEffect(() => {
        if (!isFixturesReady && !isFixturesGenerating && !hasTried) {
            setIsFixturesGenerating(true);
            setFixturesError(null);
            setTimedOut(false);
            setHasTried(true);
            // Timeout after 60s
            timeoutRef.current = setTimeout(() => {
                setTimedOut(true);
                setIsFixturesGenerating(false);
                setFixturesError('Timeout: Store preset generation took too long. Please try again.');
            }, 60000);
            handleCreateFixtures()
                .then(() => {
                    clearTimeout(timeoutRef.current);
                    setIsFixturesReady(true);
                })
                .catch((err) => {
                    clearTimeout(timeoutRef.current);
                    setFixturesError(err?.message || 'Unknown error');
                })
                .finally(() => {
                    setIsFixturesGenerating(false);
                });
        }
        return () => clearTimeout(timeoutRef.current);
    }, [isFixturesReady, isFixturesGenerating, hasTried, handleCreateFixtures, setFixturesError, setIsFixturesReady, setIsFixturesGenerating]);

    const handleRetry = () => {
        setHasTried(false);
        setFixturesError(null);
        setTimedOut(false);
    };

    if (!isFixturesReady && !fixturesError) {
        return (
            <div className="flex flex-col items-center justify-center min-h-[40vh]">
                <div className="mb-4">Generating your store preset, please wait...</div>
                <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-teal-600"></div>
            </div>
        );
    }
    if (fixturesError) {
        return (
            <div className="flex flex-col items-center justify-center min-h-[40vh] gap-4">
                <div className="text-red-600 mb-2">{fixturesError}</div>
                <button
                    onClick={handleRetry}
                    className="py-2 px-6 bg-teal-600 hover:bg-teal-700 text-white rounded-md font-medium shadow transition"
                >
                    Retry
                </button>
            </div>
        );
    }
    if (isFixturesReady) {
        return <DownloadStorePresetButton suiteName={suiteName} />;
    }
    return null;
}
