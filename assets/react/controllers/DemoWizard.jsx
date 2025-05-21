// assets/react/controllers/DemoWizard.jsx
import React, {useState, useEffect} from 'react';
import {motion, AnimatePresence} from 'framer-motion';

const stepVariants = {
    hidden: {opacity: 0, x: 50},
    visible: {opacity: 1, x: 0},
    exit: {opacity: 0, x: -50},
};

const defaultLogos = [
    {id: 'sylius', name: 'Sylius', url: '/uploads/logo_sylius.png'},
    // dodaj inne domyślne loga jeśli potrzeba
];

export default function DemoWizard({
                                       apiUrl,
                                       pluginsUrl,
                                       fixturesUrl,
                                       logoUploadUrl,
                                       targetsUrl,
                                       environmentsUrl,
                                   }) {
    const [step, setStep] = useState(1);

    const [plugins, setPlugins] = useState([]);
    const [fixtures, setFixtures] = useState([]);
    const [targets, setTargets] = useState([]);

    const [selectedPlugins, setSelectedPlugins] = useState([]);
    const [selectedFixtures, setSelectedFixtures] = useState([]);

    const [selectedLogo, setSelectedLogo] = useState(defaultLogos[0].id);
    const [logoFile, setLogoFile] = useState(null);
    const [logoUrl, setLogoUrl] = useState(defaultLogos[0].url);

    const [target, setTarget] = useState('');
    const [envOptions, setEnvOptions] = useState([]);
    const [env, setEnv] = useState('');

    const [error, setError] = useState(null);
    const [result, setResult] = useState(null);

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

    // fetch environments when reaching deploy step and platform.sh selected
    useEffect(() => {
        if (step === 4 && target === 'platform.sh') {
            fetch(environmentsUrl)
                .then(r => r.json())
                .then(data => setEnvOptions(data.environments || []))
                .catch(() => setError('Błąd pobierania środowisk'));
        }
    }, [step, target, environmentsUrl]);

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

    const submit = async () => {
        try {
            if (logoFile && logoUrl === defaultLogos.find(l => l.id === selectedLogo).url) {
                await uploadLogo();
            }
            const payload = {
                plugins: selectedPlugins,
                fixtures: selectedFixtures,
                logoUrl,
                target,
                environment: target === 'platform.sh' ? env : undefined,
            };
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
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
                <div className="p-6 min-h-[380px]">
                    {error && (
                        <div className="mb-4 text-red-600 bg-red-100 p-3 rounded-lg">
                            {error}
                        </div>
                    )}

                    <AnimatePresence exitBeforeEnter>
                        {/* Step 1: Plugins */}
                        {step === 1 && (
                            <motion.div
                                key="step1"
                                variants={stepVariants}
                                initial="hidden"
                                animate="visible"
                                exit="exit"
                                transition={{duration: 0.3}}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-indigo-700">
                                    1. Wybierz pluginy
                                </h2>
                                <div className="grid grid-cols-2 gap-3 mb-6 max-h-44 overflow-y-auto">
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
                                <button
                                    onClick={next}
                                    disabled={!selectedPlugins.length}
                                    className={`w-full py-2 rounded-lg font-medium transition ${
                                        selectedPlugins.length
                                            ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow'
                                            : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                    }`}
                                >
                                    Dalej →
                                </button>
                            </motion.div>
                        )}

                        {/* Step 2: Fixtures */}
                        {step === 2 && (
                            <motion.div
                                key="step2"
                                variants={stepVariants}
                                initial="hidden"
                                animate="visible"
                                exit="exit"
                                transition={{duration: 0.3}}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-indigo-700">
                                    2. Wybierz fixtures
                                </h2>
                                <div className="grid grid-cols-2 gap-3 mb-6 max-h-44 overflow-y-auto">
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

                        {/* Step 3: Logo */}
                        {step === 3 && (
                            <motion.div
                                key="step3"
                                variants={stepVariants}
                                initial="hidden"
                                animate="visible"
                                exit="exit"
                                transition={{duration: 0.3}}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-indigo-700">
                                    3. Wybierz lub wgraj logo
                                </h2>
                                <div className="grid grid-cols-2 gap-3 mb-4">
                                    {defaultLogos.map(l => (
                                        <label key={l.id} className="flex items-center space-x-2">
                                            <input
                                                type="radio"
                                                name="logo"
                                                value={l.id}
                                                checked={selectedLogo === l.id}
                                                onChange={() => {
                                                    setSelectedLogo(l.id);
                                                    setLogoUrl(l.url);
                                                    setLogoFile(null);
                                                }}
                                                className="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                            />
                                            <img src={l.url} alt={l.name} className="h-8 w-8 object-contain"/>
                                            <span className="text-gray-700">{l.name}</span>
                                        </label>
                                    ))}
                                </div>
                                <div className="mb-4">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        onChange={e => {
                                            const file = e.target.files[0];
                                            setLogoFile(file);
                                            setLogoUrl(file ? URL.createObjectURL(file) : logoUrl);
                                        }}
                                        className="w-full text-gray-700"
                                    />
                                </div>
                                {logoUrl && (
                                    <div className="mb-4">
                                        <p className="text-gray-700 mb-2">Podgląd:</p>
                                        <img src={logoUrl} alt="Logo podgląd" className="h-16 object-contain"/>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <button onClick={back} className="text-indigo-600 hover:underline">
                                        ← Wstecz
                                    </button>
                                    <button
                                        onClick={next}
                                        className="py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow transition"
                                    >
                                        Dalej →
                                    </button>
                                </div>
                            </motion.div>
                        )}

                        {/* Step 4: Deploy */}
                        {step === 4 && (
                            <motion.div
                                key="step4"
                                variants={stepVariants}
                                initial="hidden"
                                animate="visible"
                                exit="exit"
                                transition={{duration: 0.3}}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-indigo-700">
                                    4. Gdzie deploy?
                                </h2>
                                <div className="mb-4">
                                    {targets.map(t => (
                                        <label key={t} className="flex items-center space-x-2 mb-2">
                                            <input
                                                type="radio"
                                                name="target"
                                                value={t}
                                                checked={target === t}
                                                onChange={() => {
                                                    setTarget(t);
                                                    setEnv('');
                                                }}
                                                className="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                            />
                                            <span className="text-gray-700">{t}</span>
                                        </label>
                                    ))}
                                </div>
                                {target === 'platform.sh' && (
                                    <div className="mb-4">
                                        <select
                                            value={env}
                                            onChange={e => setEnv(e.target.value)}
                                            className="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                        >
                                            <option value="">— wybierz środowisko —</option>
                                            {envOptions.map(e => (
                                                <option key={e} value={e}>{e}</option>
                                            ))}
                                        </select>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <button onClick={back} className="text-indigo-600 hover:underline">
                                        ← Wstecz
                                    </button>
                                    <button
                                        onClick={submit}
                                        disabled={
                                            !target ||
                                            (target === 'platform.sh' && !env)
                                        }
                                        className={`py-2 px-4 rounded-lg font-medium transition ${
                                            target && (target !== 'platform.sh' || env)
                                                ? 'bg-green-600 hover:bg-green-700 text-white shadow'
                                                : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                                        }`}
                                    >
                                        Utwórz demo
                                    </button>
                                </div>
                            </motion.div>
                        )}

                        {/* Step 5: Result */}
                        {step === 5 && result && (
                            <motion.div
                                key="step5"
                                variants={stepVariants}
                                initial="hidden"
                                animate="visible"
                                exit="exit"
                                transition={{duration: 0.3}}
                            >
                                <h2 className="text-xl font-semibold mb-4 text-indigo-700">
                                    5. Wynik deploy’u
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
