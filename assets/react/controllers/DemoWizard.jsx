import React, {useState, useEffect} from 'react';
import {motion, AnimatePresence} from 'framer-motion';
import FixtureWizard from './FixtureWizard';
import {useSupportedPlugins} from '../hooks/useSupportedPlugins';

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
    'Logo',
    'Deploy',
    'Summary',
];

const stepTitles = [
    'Choose plugins',
    'Describe your store',
    'Upload your logo',
    'Choose deployment target',
    'Summary',
];
const stepDescriptions = [
    'Select the plugins you want to include in your demo store.',
    'Provide a description of your store and its details.',
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

function FixturesStep({fixtures, selectedFixtures, setSelectedFixtures, onFixturesGenerated}) {
    // Render FixtureWizard and handle fixture selection
    return (
        <div>
            <h2 className="text-xl font-semibold mb-4 text-teal-700">2. Fixtures</h2>
            <div className="mb-6">
                <FixtureWizard onFixturesGenerated={onFixturesGenerated}/>
            </div>
            {/* Optionally, show selected fixtures summary here */}
            <div className="flex justify-between">
                {/* Back/Next handled in parent */}
            </div>
        </div>
    );
}

export default function DemoWizard({
                                       apiUrl,
                                       logoUploadUrl,
                                       environmentsUrl,
                                       deployStateUrlBase
                                   }) {
    const [step, setStep] = useState(1);
    const [direction, setDirection] = useState(1); // 1 = next, -1 = back
    const {plugins, loading: pluginsLoading, error: pluginsError} = useSupportedPlugins();
    const [fixtures, setFixtures] = useState([]);
    const [targets, setTargets] = useState([]);
    const [selectedPlugins, setSelectedPlugins] = useState([]);
    const [selectedFixtures, setSelectedFixtures] = useState([]);
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

    const next = () => {
        setDirection(1);
        setStep(s => s + 1);
    };
    const back = () => {
        setDirection(-1);
        setStep(s => s - 1);
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
                fixtures: selectedFixtures,
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

    // Callback to update fixtures from FixtureWizard
    const handleFixturesGenerated = (newFixtures) => {
        setFixtures(newFixtures);
        setSelectedFixtures(newFixtures); // or custom logic
    };

    return (
        <motion.div
            initial={{ opacity: 0, scale: 0.98, y: 30 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            transition={{ duration: 0.5, ease: 'easeOut' }}
            className="w-full flex-1 max-w-xl mx-auto py-6 flex flex-col justify-between"
            style={{padding: 0}}
        >
            <div className="mb-6">
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
            <div className="border-b border-gray-100 pb-0 flex-1 relative" style={{minHeight: 0}}>
                {error && <div
                    className="mb-4 text-teal-700 bg-teal-50 p-3 rounded-lg border border-teal-100 text-sm">{error}</div>}
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
                                        onClick={next}
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
                    {/* Step 2: Fixtures (FixtureWizard) */}
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
                            <div className="w-full min-h-[70vh] flex justify-center items-center" style={{padding: 0}}>
                                <div className="w-full max-w-5xl mx-auto flex justify-center items-center"
                                     style={{padding: 0}}>
                                    <FixtureWizard onFixturesGenerated={handleFixturesGenerated}/>
                                </div>
                            </div>
                            <div className="flex justify-between mt-6">
                                <button onClick={back} className="text-teal-600 hover:underline rounded-lg px-4 py-2">←
                                    Back
                                </button>
                                <button
                                    onClick={next}
                                    disabled={!fixtures.length}
                                    className={`py-2 px-4 rounded-lg font-medium transition ${
                                        fixtures.length ? 'bg-teal-600 hover:bg-teal-700 text-white' : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200'
                                    }`}
                                >Next →
                                </button>
                            </div>
                        </motion.div>
                    )}
                    {/* Step 3: Logo Upload */}
                    {step === 3 && (
                        <motion.div
                            key="3"
                            custom={direction}
                            variants={stepVariants}
                            initial="enter"
                            animate="center"
                            exit="exit"
                            transition={{duration: 0.35, type: 'tween'}}
                        >
                            <h2 className="text-xl font-semibold mb-4 text-teal-700">3. Logo</h2>
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
                                <button onClick={next}
                                        className="py-2 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-medium shadow transition">Next
                                    →
                                </button>
                            </div>
                        </motion.div>
                    )}
                    {/* Step 4: Deploy Target */}
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
                            <h2 className="text-xl font-semibold mb-4 text-teal-700">4. Where to deploy?</h2>
                            <div className="mb-4 space-y-2">
                                {targets.map(t => (
                                    <label key={t} className="flex items-center space-x-2">
                                        <input
                                            type="radio"
                                            name="target"
                                            value={t}
                                            checked={target === t}
                                            onChange={() => {
                                                setTarget(t);
                                                setEnv('');
                                            }}
                                            className="h-4 w-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
                                        />
                                        <span className="text-gray-800 text-sm">{t}</span>
                                    </label>
                                ))}
                            </div>
                            {target === 'platform.sh' && (
                                <select
                                    value={env}
                                    onChange={e => setEnv(e.target.value)}
                                    className="w-full mb-4 border border-gray-300 rounded-md p-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"
                                >
                                    <option value="">— choose environment —</option>
                                    {envOptions.map(e => (
                                        <option key={e} value={e}>{e}</option>
                                    ))}
                                </select>
                            )}
                            <div className="flex justify-between items-center">
                                <button onClick={back} className="text-teal-600 hover:underline rounded-lg px-4 py-2">←
                                    Back
                                </button>
                                <button
                                    onClick={handleDeploy}
                                    disabled={loading || !target || (target === 'platform.sh' && !env)}
                                    className={`py-2 px-4 rounded-lg font-medium transition flex items-center space-x-2 ${
                                        !loading && target && (target !== 'platform.sh' || env)
                                            ? 'bg-green-600 hover:bg-green-700 text-white'
                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200'
                                    }`}
                                >
                                    {loading ? (
                                        <svg className="animate-spin h-5 w-5 text-white"
                                             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    strokeWidth="4"/>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                        </svg>
                                    ) : 'Deploy'}
                                </button>
                            </div>
                        </motion.div>
                    )}
                    {/* Step 5: Summary & Deploy Button */}
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
        </motion.div>
    );
}
