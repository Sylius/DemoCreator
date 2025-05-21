// assets/react/controllers/DemoWizard.jsx
import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';

const stepVariants = {
    hidden:  { opacity: 0, x: 50 },
    visible: { opacity: 1, x: 0 },
    exit:    { opacity: 0, x: -50 },
};

export default function DemoWizard({
                                       apiUrl,
                                       pluginsUrl,
                                       fixturesUrl,
                                       logoUploadUrl,
                                       targetsUrl,
                                   }) {
    const [step, setStep] = useState(1);
    const [plugins, setPlugins]   = useState([]);
    const [fixtures, setFixtures] = useState([]);
    const [targets, setTargets]   = useState([]);
    const [selectedPlugins, setSelectedPlugins]   = useState([]);
    const [selectedFixtures, setSelectedFixtures] = useState([]);
    const [env, setEnv]         = useState('');
    const [logoFile, setLogoFile] = useState(null);
    const [logoUrl, setLogoUrl]   = useState('');
    const [target, setTarget]     = useState('');
    const [error, setError]       = useState(null);
    const [result, setResult]     = useState(null);

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

    const next = () => setStep(s => s + 1);
    const back = () => setStep(s => s - 1);

    const uploadLogo = async () => {
        const form = new FormData();
        form.append('logo', logoFile);
        const res = await fetch(logoUploadUrl, { method: 'POST', body: form });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error);
        setLogoUrl(data.logoUrl);
    };

    const submit = async () => {
        try {
            if (logoFile && !logoUrl) await uploadLogo();
            const payload = {
                environment: env,
                plugins:     selectedPlugins,
                fixtures:    selectedFixtures,
                logoUrl,
                target,
            };
            const res = await fetch(apiUrl, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message);
            setResult(data);
            next();
        } catch (e) {
            setError(e.message);
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-teal-50 to-indigo-50 p-4">
            <div className="w-full max-w-md bg-white border border-gray-200 rounded-2xl shadow-xl overflow-hidden">
                <div className="bg-indigo-600 text-white py-4 px-6">
                    <h1 className="text-2xl font-bold">Kreator demo</h1>
                </div>
                <div className="p-6">
                    {error && (
                        <div className="mb-4 text-red-600 bg-red-100 p-3 rounded-lg">
                            {error}
                        </div>
                    )}

                    <AnimatePresence exitBeforeEnter>
                        {/* Step 1: Environment */}
                        {step === 1 && (
                            <motion.div
                                key="step1"
                                variants={stepVariants}
                                initial="hidden"
                                animate="visible"
                                exit="exit"
                                transition={{ duration: 0.3 }}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-indigo-700">
                                    1. Wybierz środowisko
                                </h2>
                                <select
                                    value={env}
                                    onChange={e => setEnv(e.target.value)}
                                    className="w-full border border-gray-300 rounded-lg p-3 mb-6 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                >
                                    <option value="">— wybierz —</option>
                                    <option value="booster">booster</option>
                                </select>
                                <button
                                    onClick={next}
                                    disabled={!env}
                                    className={`w-full py-2 rounded-lg font-medium transition ${
                                        env
                                            ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow'
                                            : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                    }`}
                                >
                                    Dalej
                                </button>
                            </motion.div>
                        )}

                        {/* Step 2: Plugins */}
                        {step === 2 && (
                            <motion.div
                                key="step2"
                                variants={stepVariants}
                                initial="hidden"
                                animate="visible"
                                exit="exit"
                                transition={{ duration: 0.3 }}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-indigo-700">
                                    2. Pluginy
                                </h2>
                                <div className="grid grid-cols-2 gap-3 mb-6">
                                    {plugins.map(p => (
                                        <label key={p} className="flex items-center space-x-2">
                                            <input
                                                type="checkbox"
                                                value={p}
                                                onChange={e => {
                                                    const v = e.target.value;
                                                    setSelectedPlugins(sel =>
                                                        e.target.checked ? [...sel, v] : sel.filter(x => x !== v)
                                                    );
                                                }}
                                                className="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                            />
                                            <span className="text-gray-700">{p}</span>
                                        </label>
                                    ))}
                                </div>
                                <div className="flex justify-between">
                                    <button onClick={back} className="text-indigo-600 hover:underline">
                                        ← Wstecz
                                    </button>
                                    <button
                                        onClick={next}
                                        disabled={!selectedPlugins.length}
                                        className={`py-2 px-4 rounded-lg font-medium transition ${
                                            selectedPlugins.length
                                                ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow'
                                                : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                        }`}
                                    >
                                        Dalej →
                                    </button>
                                </div>
                            </motion.div>
                        )}

                        {/* Step 3: Fixtures */}
                        {step === 3 && (
                            <motion.div
                                key="step3"
                                variants={stepVariants}
                                initial="hidden"
                                animate="visible"
                                exit="exit"
                                transition={{ duration: 0.3 }}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-indigo-700">
                                    3. Fixtures
                                </h2>
                                <div className="grid grid-cols-2 gap-3 mb-6">
                                    {fixtures.map(f => (
                                        <label key={f} className="flex items-center space-x-2">
                                            <input
                                                type="checkbox"
                                                value={f}
                                                onChange={e => {
                                                    const v = e.target.value;
                                                    setSelectedFixtures(sel =>
                                                        e.target.checked ? [...sel, v] : sel.filter(x => x !== v)
                                                    );
                                                }}
                                                className="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                            />
                                            <span className="text-gray-700">{f}</span>
                                        </label>
                                    ))}
                                </div>
                                <div className="flex justify-between">
                                    <button onClick={back} className="text-indigo-600 hover:underline">
                                        ← Wstecz
                                    </button>
                                    <button
                                        onClick={next}
                                        disabled={!selectedFixtures.length}
                                        className={`py-2 px-4 rounded-lg font-medium transition ${
                                            selectedFixtures.length
                                                ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow'
                                                : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                        }`}
                                    >
                                        Dalej →
                                    </button>
                                </div>
                            </motion.div>
                        )}

                        {/* Step 4: Logo Upload */}
                        {step === 4 && (
                            <motion.div
                                key="step4"
                                variants={stepVariants}
                                initial="hidden"
                                animate="visible"
                                exit="exit"
                                transition={{ duration: 0.3 }}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-indigo-700">
                                    4. Wgraj logo
                                </h2>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={e => setLogoFile(e.target.files[0])}
                                    className="w-full text-gray-700 mb-4"
                                />
                                {logoFile && (
                                    <p className="mb-4 text-gray-600">Wybrane: {logoFile.name}</p>
                                )}
                                <div className="flex justify-between">
                                    <button onClick={back} className="text-indigo-600 hover:underline">
                                        ← Wstecz
                                    </button>
                                    <button
                                        onClick={next}
                                        disabled={!logoFile}
                                        className={`py-2 px-4 rounded-lg font-medium transition ${
                                            logoFile
                                                ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow'
                                                : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                        }`}
                                    >
                                        Dalej →
                                    </button>
                                </div>
                            </motion.div>
                        )}

                        {/* Step 5: Deploy Target */}
                        {step === 5 && (
                            <motion.div
                                key="step5"
                                variants={stepVariants}
                                initial="hidden"
                                animate="visible"
                                exit="exit"
                                transition={{ duration: 0.3 }}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-indigo-700">
                                    5. Gdzie deploy?
                                </h2>
                                <select
                                    value={target}
                                    onChange={e => setTarget(e.target.value)}
                                    className="w-full border border-gray-300 rounded-lg p-3 mb-6 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                >
                                    <option value="">— wybierz —</option>
                                    {targets.map(t => (
                                        <option key={t} value={t}>{t}</option>
                                    ))}
                                </select>
                                <div className="flex justify-between">
                                    <button onClick={back} className="text-indigo-600 hover:underline">
                                        ← Wstecz
                                    </button>
                                    <button
                                        onClick={submit}
                                        disabled={!target}
                                        className={`py-2 px-4 rounded-lg font-medium transition ${
                                            target
                                                ? 'bg-green-600 hover:bg-green-700 text-white shadow'
                                                : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                        }`}
                                    >
                                        Utwórz demo
                                    </button>
                                </div>
                            </motion.div>
                        )}

                        {/* Step 6: Result */}
                        {step === 6 && result && (
                            <motion.div
                                key="step6"
                                variants={stepVariants}
                                initial="hidden"
                                animate="visible"
                                exit="exit"
                                transition={{ duration: 0.3 }}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-indigo-700">
                                    6. Wynik deploy’u
                                </h2>
                                <pre className="bg-gray-100 p-4 rounded-lg overflow-x-auto">
                  {JSON.stringify(result, null, 2)}
                </pre>
                                <div className="mt-6 text-center">
                                    <button
                                        onClick={() => window.location.reload()}
                                        className="py-2 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow transition"
                                    >
                                        Utwórz kolejne demo
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
