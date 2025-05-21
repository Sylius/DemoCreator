import React, {useState, useEffect} from 'react';
import {motion, AnimatePresence} from 'framer-motion';

const stepVariants = {
    hidden: {opacity: 0, x: 50},
    visible: {opacity: 1, x: 0},
    exit: {opacity: 0, x: -50},
};

export default function DemoWizard({
                                       apiUrl,
                                       pluginsUrl,
                                       fixturesUrl,
                                       logoUploadUrl,
                                       targetsUrl,
                                       environmentsUrl,
                                       deployStateUrlBase
                                   }) {
    const [step, setStep] = useState(1);

    const [plugins, setPlugins] = useState([]);
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
        Promise.all([
            fetch(pluginsUrl).then(r => r.json()),
            fetch(fixturesUrl).then(r => r.json()),
            fetch(targetsUrl).then(r => r.json()),
        ])
            .then(([p, f, t]) => {
                setPlugins(p.plugins);
                setFixtures(f.fixtures);
                setTargets(t.targets);
            })
            .catch(() => setError('Nie udało się załadować konfiguracji'));
    }, [pluginsUrl, fixturesUrl, targetsUrl]);

    useEffect(() => {
        if (step === 4 && target === 'platform.sh') {
            fetch(environmentsUrl)
                .then(r => r.json())
                .then(data => setEnvOptions(data.environments || []))
                .catch(() => setError('Błąd pobierania środowisk'));
        }
    }, [step, target, environmentsUrl]);

    // Poll deploy state
    useEffect(() => {
        if (step === 5 && deployStateId) {
            console.log('Polling deploy state...');
            const interval = setInterval(() => {
                console.log('Interval start');
                fetch(`${deployStateUrlBase}/${env}/${deployStateId}`)
                    .then(r => r.json())
                    .then(data => {
                        console.log('Polling response:', data);
                        setDeployStatus(data.status);
                        if (data.status !== 'in_progress') {
                            console.log('Stopping interval:', data.status);
                            clearInterval(interval);
                        }
                    })
                    .catch(() => {
                        console.error('Error fetching deploy state');
                        setError('Błąd sprawdzania statusu deploy');
                        clearInterval(interval);
                    });
            }, 20000);
            return () => clearInterval(interval);
        }
    }, [step, deployStateId, env, deployStateUrlBase]);

    const next = () => setStep(s => s + 1);
    const back = () => setStep(s => s - 1);

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

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50 p-4 font-sans">
            <div className="w-full max-w-md bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                <div className="bg-teal-600 text-white py-4 px-6">
                    <h1 className="text-2xl font-semibold">Kreator Demo</h1>
                </div>
                <div className="p-6 min-h-[400px]">
                    {error && <div className="mb-4 text-teal-700 bg-teal-100 p-3 rounded">{error}</div>}

                    <AnimatePresence exitBeforeEnter>
                        {/* Step 1: Plugins */}
                        {step === 1 && (
                            <motion.div key="1" variants={stepVariants} initial="hidden" animate="visible" exit="exit"
                                        transition={{duration: 0.3}}>
                                <h2 className="text-lg font-medium mb-4 text-teal-700">1. Pluginy</h2>
                                <div className="grid grid-cols-1 gap-2 mb-6 max-h-40 overflow-y-auto">
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
                                            <span className="text-gray-800 text-sm">{p.name} ({p.version})</span>
                                        </label>
                                    ))}
                                </div>
                                <button
                                    onClick={next}
                                    disabled={!selectedPlugins.length}
                                    className={`w-full py-2 rounded-md text-white font-medium transition ${
                                        selectedPlugins.length ? 'bg-teal-600 hover:bg-teal-700' : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                    }`}
                                >Dalej →
                                </button>
                            </motion.div>
                        )}

                        {/* Step 2: Fixtures */}
                        {step === 2 && (
                            <motion.div key="2" variants={stepVariants} initial="hidden" animate="visible" exit="exit"
                                        transition={{duration: 0.3}}>
                                <h2 className="text-lg font-medium mb-4 text-teal-700">2. Fixtures</h2>
                                <div className="grid grid-cols-1 gap-2 mb-6 max-h-32 overflow-y-auto">
                                    {fixtures.map(f => (
                                        <label key={f} className="flex items-center space-x-2">
                                            <input
                                                type="checkbox"
                                                value={f}
                                                checked={selectedFixtures.includes(f)}
                                                onChange={e => {
                                                    const v = e.target.value;
                                                    setSelectedFixtures(sel => sel.includes(v) ? sel.filter(x => x !== v) : [...sel, v]);
                                                }}
                                                className="h-4 w-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
                                            />
                                            <span className="text-gray-800 text-sm">{f}</span>
                                        </label>
                                    ))}
                                </div>
                                <div className="flex justify-between">
                                    <button onClick={back} className="text-teal-600 hover:underline">← Wstecz</button>
                                    <button
                                        onClick={next}
                                        disabled={!selectedFixtures.length}
                                        className={`py-2 px-4 rounded-md font-medium transition ${
                                            selectedFixtures.length ? 'bg-teal-600 hover:bg-teal-700 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                        }`}
                                    >Dalej →
                                    </button>
                                </div>
                            </motion.div>
                        )}

                        {/* Step 3: Logo Upload */}
                        {step === 3 && (
                            <motion.div key="3" variants={stepVariants} initial="hidden" animate="visible" exit="exit"
                                        transition={{duration: 0.3}}>
                                <h2 className="text-lg font-medium mb-4 text-teal-700">3. Logo</h2>
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
                                    <button onClick={back} className="text-teal-600 hover:underline">← Wstecz</button>
                                    <button onClick={next}
                                            className="py-2 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-md shadow transition">Dalej
                                        →
                                    </button>
                                </div>
                            </motion.div>
                        )}

                        {/* Step 4: Deploy Target */}
                        {step === 4 && (
                            <motion.div key="4" variants={stepVariants} initial="hidden" animate="visible" exit="exit"
                                        transition={{duration: 0.3}}>
                                <h2 className="text-lg font-medium mb-4 text-teal-700">4. Gdzie deploy?</h2>
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
                                        <option value="">— wybierz środowisko —</option>
                                        {envOptions.map(e => (
                                            <option key={e} value={e}>{e}</option>
                                        ))}
                                    </select>
                                )}
                                <div className="flex justify-between items-center">
                                    <button onClick={back} className="text-teal-600 hover:underline">← Wstecz</button>
                                    <button
                                        onClick={handleDeploy}
                                        disabled={loading || !target || (target === 'platform.sh' && !env)}
                                        className={`py-2 px-4 rounded-md font-medium transition flex items-center space-x-2 ${
                                            !loading && target && (target !== 'platform.sh' || env)
                                                ? 'bg-green-600 hover:bg-green-700 text-white'
                                                : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                        }`}
                                    >
                                        {loading ? (
                                            <svg className="animate-spin h-5 w-5 text-white"
                                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" strokeWidth="4"/>
                                                <path className="opacity-75" fill="currentColor"
                                                      d="M4 12a8 8 0 018-8v8z"/>
                                            </svg>
                                        ) : 'Deploy'}
                                    </button>
                                </div>
                            </motion.div>
                        )}

                        {/* Step 5: Summary & Deploy Button */}
                        {step === 5 && (
                            <motion.div key="5" variants={stepVariants} initial="hidden" animate="visible" exit="exit"
                                        transition={{duration: 0.3}}>
                                <h2 className="text-lg font-medium mb-4 text-teal-700">5. Podsumowanie & Deploy</h2>
                                {/* Summary omitted for brevity */}
                                <div className="flex justify-center">
                                    <button
                                        disabled={deployStatus !== 'complete'}
                                        onClick={() => window.open(deployUrl, '_blank')}
                                        className={`py-2 px-4 rounded-md font-medium transition ${
                                            deployStatus === 'complete'
                                                ? 'bg-green-600 hover:bg-green-700 text-white'
                                                : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                        } flex items-center space-x-2 mx-auto`}
                                    >
                                        {deployStatus === 'in_progress' && (
                                            <svg className="animate-spin h-5 w-5 text-white"
                                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" strokeWidth="4"/>
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
        </div>
    );
}
