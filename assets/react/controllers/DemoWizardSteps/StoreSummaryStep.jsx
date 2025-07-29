import {useState, useCallback, useContext} from 'react';
import {WizardContext} from "../../hooks/WizardProvider";
import {motion} from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';
import {useStorePreset} from "../../hooks/useStorePreset";

export default function StoreSummaryStep() {
    const { wiz, dispatch } = useContext(WizardContext);
    const {
        loading: presetLoading,
        error: presetError,
        generateStore,
        deployStore,
    } = useStorePreset();
    const [localError, setLocalError] = useState(null);

    const prettify = (name) =>
        name
            .replace(/^sylius\//, '')
            .replace(/-plugin$/, '')
            .replace(/-/g, ' ')
            .replace(/\b\w/g, (l) => l.toUpperCase());

    const back = useCallback(() => {
        dispatch({ type: 'SET_STEP', step: Math.max(wiz.step - 1, 1), direction: -1 });
    }, [wiz.step, dispatch]);

    const handleGenerate = async () => {
        dispatch({ type: 'BEGIN_GENERATION' });
        setLocalError(null);
        try {
            await generateStore(wiz.storeDetails);
            dispatch({ type: 'READY_TO_DEPLOY' });
        } catch (e) {
            const errMsg =
                e?.response?.data?.error ||
                e?.message ||
                presetError ||
                'Unknown error';
            dispatch({ type: 'ERROR', error: errMsg });
            setLocalError(errMsg);
        }
    };

    const handleDeploy = async () => {
        dispatch({ type: 'BEGIN_DEPLOY' });
        setLocalError(null);
        try {
            await deployStore();
            dispatch({ type: 'COMPLETE' });
        } catch (e) {
            const errMsg =
                e?.response?.data?.error ||
                e?.message ||
                presetError ||
                'Unknown error';
            dispatch({ type: 'ERROR', error: errMsg });
            setLocalError(errMsg);
        }
    }

    let content;
    switch (wiz.state) {
        case 'ready':
            content = (
                <button
                    onClick={handleGenerate}
                    disabled={presetLoading}
                    className="w-full py-2 rounded-lg font-medium bg-teal-600 hover:bg-teal-700 text-white disabled:opacity-50"
                >
                    {presetLoading ? 'Initializing...' : 'Begin Store Generation'}
                </button>
            );
            break;

        case 'generating':
            content = (
                <div className="flex flex-col items-center py-6">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-4 border-teal-600 mb-4" />
                    <p className="text-gray-700 font-semibold">Generating store and images…</p>
                </div>
            );
            break;

        case 'readyToDeploy':
            content = (
                <button
                    onClick={handleDeploy}
                    disabled={presetLoading}
                    className="w-full py-2 rounded-lg font-medium bg-teal-600 hover:bg-teal-700 text-white disabled:opacity-50"
                >
                    {presetLoading ? 'Initializing...' : 'Begin Store Deployment'}
                </button>
            );
            break;

        case 'deploying':
            content = (
                <div className="flex flex-col items-center py-6">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-4 border-teal-600 mb-4" />
                    <p className="text-gray-700 font-semibold">Deploying store…</p>
                </div>
            );
            break;

        case 'complete':
            content = (
                <div className="flex flex-col items-center py-6">
                    <div className="text-green-600 text-3xl mb-2">🚀</div>
                    <p className="text-green-700 font-semibold">Store successfully deployed!</p>
                </div>
            );
            break;

        case 'error':
            content = (
                <div className="flex flex-col items-center py-6">
                    <div className="text-red-600 text-3xl mb-2">✖</div>
                    <p className="text-red-700 font-semibold mb-4">{localError || presetError}</p>
                    <button
                        onClick={handleGenerateAndDeploy}
                        className="w-full py-2 rounded-lg font-medium bg-teal-600 hover:bg-teal-700 text-white"
                    >
                        Retry
                    </button>
                </div>
            );
            break;

        default:
            content = null;
    }

    return (
        <motion.div
            key="4"
            custom={wiz.direction}
            variants={wizardStepVariants}
            initial="enter"
            animate="center"
            exit="exit"
            transition={{ duration: 0.25, type: 'tween', ease: 'easeInOut' }}
            className="p-4"
        >
            <div className="sticky top-0 mx-auto mb-4 bg-white shadow-md px-6 py-4 rounded-lg max-w-lg">
                <h2 className="text-2xl font-bold text-center text-teal-700 mb-4">Store Summary & Generation</h2>
                <div className="bg-white rounded-xl shadow p-6 border mb-6 mx-auto max-w-md">
                    <h3 className="font-semibold text-gray-800 text-center mb-2">Selected Plugins</h3>
                    {Object.keys(wiz.plugins).length === 0 ? (
                        <p className="text-center">-</p>
                    ) : (
                        <ul className="list-disc list-inside text-sm text-gray-700 mx-auto max-w-xs">
                            {Object.entries(wiz.plugins).map(([pkg, ver]) => (
                                <li key={pkg} className="text-center">
                                    {prettify(pkg)} ({ver})
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
                {content}
                <div className="flex justify-between mt-6">
                    <button onClick={back} className="text-teal-600 hover:underline text-sm">
                        ← Back
                    </button>
                </div>
            </div>
        </motion.div>
    );
}
