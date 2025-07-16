import React, {useContext, useState} from 'react';
import {WizardContext, StorePresetContext} from '../../hooks/WizardProvider';
import {motion} from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';

export default function StoreSummaryStep() {
    const {wiz, dispatch} = useContext(WizardContext);
    const {handleCreateFixtures, handleCreateImages, loading, error} = useContext(StorePresetContext);
    const [status, setStatus] = useState('idle'); // idle | generatingFixtures | generatingImages | success | error | deploying
    const [errorMsg, setErrorMsg] = useState(null);

    const prettify = (name) => {
        return name
            .replace(/^sylius\//, '')
            .replace(/-plugin$/, '')
            .replace(/-/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    }

    const onBeginGeneration = async () => {
        setStatus('generatingFixtures');
        setErrorMsg(null);
        try {
            await handleCreateFixtures();
            setStatus('generatingImages');
            await handleCreateImages();
            setStatus('success');
        } catch (e) {
            setStatus('error');
            setErrorMsg(e?.message || 'Unknown error');
        }
    };

    // Opcjonalnie: funkcja do deployu (jeśli masz handleDeploy)
    // const {handleDeploy} = useContext(StorePresetContext);
    // const onDeploy = async () => {
    //     setStatus('deploying');
    //     try {
    //         await handleDeploy();
    //         setStatus('deployed');
    //     } catch (e) {
    //         setStatus('error');
    //         setErrorMsg(e?.message || 'Unknown error');
    //     }
    // };

    return (
        <motion.div
            key="5"
            custom={wiz.direction}
            variants={wizardStepVariants}
            initial="enter"
            animate="center"
            exit="exit"
            transition={{duration: 0.25, type: 'tween', ease: 'easeInOut'}}
        >
            <h2 className="text-2xl font-bold mb-6 text-center text-teal-700">Store summary & generation</h2>
            <div className="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-white rounded-xl shadow p-6 border">
                    <h3 className="font-semibold text-gray-800 mb-2">Plugins:</h3>
                    <ul className="list-disc list-inside text-sm text-gray-700">
                        {wiz.plugins.map(str => {
                            const [composer, version] = str.split(':');
                            return (
                                <li key={str}>{prettify(composer)} ({version})</li>
                            );
                        })}
                    </ul>
                </div>
                <div className="bg-white rounded-xl shadow p-6 border">
                    <h3 className="font-semibold text-gray-800 mb-2">Deploy:</h3>
                    <p className="text-sm text-gray-700">{wiz.target}{wiz.target === 'platform.sh' && wiz.env ? ` (${wiz.env})` : ''}</p>
                </div>
            </div>

            {/* Statusy generowania */}
            {status === 'idle' && (
                <button
                    onClick={onBeginGeneration}
                    disabled={loading}
                    className="w-full py-2 rounded-lg font-medium bg-teal-600 hover:bg-teal-700 text-white"
                >
                    Begin generation
                </button>
            )}
            {status === 'generatingFixtures' && (
                <div className="flex flex-col items-center py-6">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mb-4"></div>
                    <p className="text-gray-700">Generating fixtures...</p>
                </div>
            )}
            {status === 'generatingImages' && (
                <div className="flex flex-col items-center py-6">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mb-4"></div>
                    <p className="text-gray-700">Generating images...</p>
                </div>
            )}
            {status === 'success' && (
                <div className="flex flex-col items-center py-6">
                    <div className="text-green-600 text-2xl mb-2">✔</div>
                    <p className="text-green-700 font-semibold">Generation complete!</p>
                    {/*
                    <button
                        onClick={onDeploy}
                        className="mt-4 w-full py-2 rounded-lg font-medium bg-teal-600 hover:bg-teal-700 text-white"
                    >
                        Deploy store
                    </button>
                    */}
                </div>
            )}
            {status === 'error' && (
                <div className="flex flex-col items-center py-6">
                    <div className="text-red-600 text-2xl mb-2">✖</div>
                    <p className="text-red-700 font-semibold">{errorMsg}</p>
                    <button
                        onClick={onBeginGeneration}
                        className="mt-4 w-full py-2 rounded-lg font-medium bg-teal-600 hover:bg-teal-700 text-white"
                    >
                        Retry
                    </button>
                </div>
            )}
        </motion.div>
    );
}
