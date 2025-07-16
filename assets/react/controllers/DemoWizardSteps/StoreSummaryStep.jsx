import React, {useContext, useState} from 'react';
import {WizardContext} from '../../hooks/WizardProvider';
import {motion} from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';
import {useStorePreset} from '../../hooks/useStorePreset';

export default function StoreSummaryStep() {
    const {wiz} = useContext(WizardContext);
    const {handleCreateFixtures, handleCreateImages, loading, error} = useStorePreset();
    const [status, setStatus] = useState('idle'); // idle | generatingFixtures | generatingImages | success | error
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
            <div className="flex flex-col items-center justify-center mt-8">
                <button
                    onClick={onBeginGeneration}
                    disabled={status === 'generatingFixtures' || status === 'generatingImages' || status === 'success'}
                    className="w-full max-w-md py-4 px-8 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl font-semibold shadow-lg text-xl transition-all duration-200 transform hover:scale-105 border-2 border-teal-500 mb-6"
                >
                    {status === 'generatingFixtures' ? 'Generating fixtures...' :
                        status === 'generatingImages' ? 'Generating images...' :
                            status === 'success' ? 'Generation complete!' :
                                'Begin generation'}
                </button>
                {status === 'success' && (
                    <div className="text-green-700 font-semibold text-lg mb-2">Store fixtures and images generated successfully!</div>
                )}
                {status === 'error' && (
                    <div className="text-red-600 font-semibold text-lg mb-2">Error: {errorMsg || error}</div>
                )}
                {loading && <div className="text-gray-500">Loading...</div>}
            </div>
        </motion.div>
    );
}
