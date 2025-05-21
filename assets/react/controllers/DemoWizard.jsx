import React, {useState, useEffect} from 'react';
import {Controller} from '@hotwired/stimulus';
import {render} from 'react-dom';
import {motion, AnimatePresence} from 'framer-motion';

const stepVariants = {
    hidden: {opacity: 0, x: 50},
    visible: {opacity: 1, x: 0},
    exit: {opacity: 0, x: -50},
};

export default function DemoWizard({apiUrl, pluginsUrl, fixturesUrl, logoUploadUrl, targetsUrl}) {
    const [step, setStep] = useState(1);
    // dane: plugins, fixtures, targets
    const [plugins, setPlugins] = useState([]);
    const [fixtures, setFixtures] = useState([]);
    const [targets, setTargets] = useState([]);
    // zaznaczone
    const [selectedPlugins, setSelectedPlugins] = useState([]);
    const [selectedFixtures, setSelectedFixtures] = useState([]);
    const [target, setTarget] = useState('');
    const [env, setEnv] = useState('');
    const [logoFile, setLogoFile] = useState(null);
    const [logoUrl, setLogoUrl] = useState('');
    const [error, setError] = useState('');
    const [result, setResult] = useState(null);

    useEffect(() => {
        Promise.all([
            fetch(pluginsUrl).then(r => r.json()),
            fetch(fixturesUrl).then(r => r.json()),
            fetch(targetsUrl).then(r => r.json()),
        ]).then(([p, f, t]) => {
            setPlugins(p.plugins);
            setFixtures(f.fixtures);
            setTargets(t.targets);
        }).catch(() => setError('Błąd pobierania danych konfiguracyjnych'));
    }, [pluginsUrl, fixturesUrl, targetsUrl]);

    const next = () => setStep(s => s + 1);
    const back = () => setStep(s => s - 1);

    const uploadLogo = async () => {
        const form = new FormData();
        form.append('logo', logoFile);
        const res = await fetch(logoUploadUrl, {method: 'POST', body: form});
        const data = await res.json();
        if (!res.ok) throw new Error(data.error);
        setLogoUrl(data.logoUrl);
    };

    const submit = async () => {
        try {
            if (logoFile && !logoUrl) await uploadLogo();
            const payload = {environment: env, plugins: selectedPlugins, fixtures: selectedFixtures, logoUrl, target};
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
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
        <div className="max-w-xl mx-auto p-6 bg-white shadow-lg rounded-lg">
            {error && <div className="mb-4 text-red-600">{error}</div>}

            <AnimatePresence exitBeforeEnter>
                {step === 1 && (
                    <motion.div
                        key="step1"
                        variants={stepVariants}
                        initial="hidden"
                        animate="visible"
                        exit="exit"
                        transition={{duration: 0.4}}
                    >
                        <h2 className="text-2xl font-semibold mb-4">1. Wybierz środowisko</h2>
                        <select
                            onChange={e => setEnv(e.target.value)}
                            className="w-full p-2 border rounded mb-4"
                        >
                            <option>booster</option>
                        </select>
                        <button
                            className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                            disabled={!env}
                            onClick={next}
                        >
                            Dalej
                        </button>
                    </motion.div>
                )}

                {step === 2 && (
                    <motion.div
                        key="step2"
                        variants={stepVariants}
                        initial="hidden"
                        animate="visible"
                        exit="exit"
                        transition={{duration: 0.4}}
                    >
                        <h2 className="text-2xl font-semibold mb-4">2. Pluginy</h2>
                        <div className="grid grid-cols-2 gap-2 mb-4">
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
                                    />
                                    <span>{p}</span>
                                </label>
                            ))}
                        </div>
                        <div className="flex justify-between">
                            <button onClick={back} className="text-gray-600 hover:underline">Wstecz</button>
                            <button
                                className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                disabled={!selectedPlugins.length}
                                onClick={next}
                            >
                                Dalej
                            </button>
                        </div>
                    </motion.div>
                )}

                {step === 3 && (
                    <motion.div
                        key="step3"
                        variants={stepVariants}
                        initial="hidden"
                        animate="visible"
                        exit="exit"
                        transition={{duration: 0.4}}
                    >
                        <h2 className="text-2xl font-semibold mb-4">3. Fixtures</h2>
                        <div className="grid grid-cols-2 gap-2 mb-4">
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
                                    />
                                    <span>{f}</span>
                                </label>
                            ))}
                        </div>
                        <div className="flex justify-between">
                            <button onClick={back} className="text-gray-600 hover:underline">Wstecz</button>
                            <button
                                className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                disabled={!selectedFixtures.length}
                                onClick={next}
                            >
                                Dalej
                            </button>
                        </div>
                    </motion.div>
                )}

                {step === 4 && (
                    <motion.div
                        key="step4"
                        variants={stepVariants}
                        initial="hidden"
                        animate="visible"
                        exit="exit"
                        transition={{duration: 0.4}}
                    >
                        <h2 className="text-2xl font-semibold mb-4">4. Logo</h2>
                        <input
                            type="file"
                            accept="image/*"
                            onChange={e => setLogoFile(e.target.files[0])}
                            className="mb-4"
                        />
                        {logoFile && <p className="mb-4">Wybrane: {logoFile.name}</p>}
                        <div className="flex justify-between">
                            <button onClick={back} className="text-gray-600 hover:underline">Wstecz</button>
                            <button
                                className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                disabled={!logoFile}
                                onClick={next}
                            >
                                Dalej
                            </button>
                        </div>
                    </motion.div>
                )}

                {step === 5 && (
                    <motion.div
                        key="step5"
                        variants={stepVariants}
                        initial="hidden"
                        animate="visible"
                        exit="exit"
                        transition={{duration: 0.4}}
                    >
                        <h2 className="text-2xl font-semibold mb-4">5. Gdzie deploy?</h2>
                        <select
                            value={target}
                            onChange={e => setTarget(e.target.value)}
                            className="w-full p-2 border rounded mb-4"
                        >
                            <option value="">— wybierz —</option>
                            {targets.map(t => <option key={t} value={t}>{t}</option>)}
                        </select>
                        <div className="flex justify-between">
                            <button onClick={back} className="text-gray-600 hover:underline">Wstecz</button>
                            <button
                                className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                                disabled={!target}
                                onClick={submit}
                            >
                                Utwórz demo
                            </button>
                        </div>
                    </motion.div>
                )}

                {step === 6 && result && (
                    <motion.div
                        key="step6"
                        variants={stepVariants}
                        initial="hidden"
                        animate="visible"
                        exit="exit"
                        transition={{duration: 0.4}}
                    >
                        <h2 className="text-2xl font-semibold mb-4">Wynik deploy’u</h2>
                        <pre className="bg-gray-100 p-4 rounded">{JSON.stringify(result, null, 2)}</pre>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}
