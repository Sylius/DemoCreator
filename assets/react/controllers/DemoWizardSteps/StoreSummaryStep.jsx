import React, {useContext, useEffect, useState} from 'react';
import {WizardContext, StorePresetContext} from '../../hooks/WizardProvider';
import {motion} from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';
import axios from 'axios';
import {useStorePreset} from "../../hooks/useStorePreset";

export default function StoreSummaryStep() {
    const {wiz, dispatch} = useContext(WizardContext);
    const {handleCreateFixtures, handleCreateImages, loading} = useContext(StorePresetContext);
    const [errorMsg, setErrorMsg] = useState(null);
    const [polling, setPolling] = useState(false);
    const {presetId} = useStorePreset();

    const prettify = (name) => {
        return name
            .replace(/^sylius\//, '')
            .replace(/-plugin$/, '')
            .replace(/-/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    };

    // Rozpocznij generowanie fixtures
    const onBeginGeneration = async () => {
        dispatch({type: 'START_FIXTURES'});
        setErrorMsg(null);
        try {
            await handleCreateFixtures();
            dispatch({type: 'FIXTURES_SUCCESS'});
        } catch (e) {
            dispatch({type: 'FIXTURES_ERROR', error: e?.message || 'Unknown error'});
            setErrorMsg(e?.message || 'Unknown error');
        }
    };

    // Automatyczne generowanie obrazków po fixtures
    useEffect(() => {
        if (wiz.fixtures.ready && !wiz.images.ready && !wiz.images.generating) {
            dispatch({type: 'START_IMAGES'});
            handleCreateImages()
                .then(() => dispatch({type: 'IMAGES_SUCCESS'}))
                .catch(e => dispatch({type: 'IMAGES_ERROR', error: e?.message || 'Unknown error'}));
        }
    }, [wiz.fixtures.ready, wiz.images.ready, wiz.images.generating, handleCreateImages, dispatch]);

    // Deploy logic
    const onDeploy = async () => {
        setErrorMsg(null);
        dispatch({type: 'DEPLOY_START'});
        setDeployingAnimation(true); // NEW: show animation immediately
        try {
            const env = wiz.env;
            if (!presetId || !env) throw new Error('Brak presetId lub env');
            const res = await axios.post(`/api/store-presets/${presetId}/create-demo`);
            dispatch({type: 'DEPLOY_INITIATED', stateId: res.data.activityId, url: res.data.url});
            setPolling(true);
        } catch (e) {
            dispatch({type: 'DEPLOY_ERROR', error: e?.response?.data?.error || e?.message || 'Unknown error'});
            setErrorMsg(e?.response?.data?.error || e?.message || 'Unknown error');
        }
        setDeployingAnimation(false); // hide animation after response
    };

    // NEW: local state for deploy animation
    const [deployingAnimation, setDeployingAnimation] = useState(false);

    // Polling deploy state
    useEffect(() => {
        let interval;
        if (polling && wiz.deploy.stateId && wiz.env) {
            interval = setInterval(async () => {
                try {
                    const res = await axios.get(`/api/deploy-state/${wiz.env}/${wiz.deploy.stateId}`);
                    if (res.data.status === 'complete') {
                        dispatch({type: 'DEPLOY_COMPLETE'});
                        setPolling(false);
                    }
                } catch (e) {
                    dispatch({type: 'DEPLOY_ERROR', error: e?.response?.data?.error || e?.message || 'Unknown error'});
                    setErrorMsg(e?.response?.data?.error || e?.message || 'Unknown error');
                    setPolling(false);
                }
            }, 30000);
        }
        return () => clearInterval(interval);
    }, [polling, wiz.deploy.stateId, wiz.env, dispatch]);

    // UI rendering logic based on global state
    let content = null;
    if (deployingAnimation) {
        content = (
            <div className="flex flex-col items-center py-10">
                <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-teal-600 mb-6"></div>
                <p className="text-teal-700 font-bold text-lg">Rozpoczynanie deployowania sklepu...</p>
            </div>
        );
    } else if (!wiz.fixtures.ready && !wiz.fixtures.generating) {
        content = (
            <button
                onClick={onBeginGeneration}
                disabled={loading}
                className="w-full py-2 rounded-lg font-medium bg-teal-600 hover:bg-teal-700 text-white"
            >
                Rozpocznij generowanie danych sklepu
            </button>
        );
    } else if (wiz.fixtures.generating) {
        content = (
            <div className="flex flex-col items-center py-6">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mb-4"></div>
                <p className="text-gray-700 font-semibold">Generowanie danych sklepu (fixtures)...</p>
            </div>
        );
    } else if (wiz.fixtures.ready && wiz.images.generating) {
        content = (
            <div className="flex flex-col items-center py-6">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mb-4"></div>
                <p className="text-gray-700 font-semibold">Generowanie obrazków produktów...</p>
            </div>
        );
    } else if (wiz.fixtures.ready && wiz.images.ready && !wiz.deploy.stateId) {
        content = (
            <div className="flex flex-col items-center py-6">
                <div className="text-green-600 text-2xl mb-2">🖼️</div>
                <p className="text-green-700 font-semibold">Obrazki produktów wygenerowane!</p>
                <button
                    onClick={onDeploy}
                    className="mt-4 w-full py-2 rounded-lg font-medium bg-teal-600 hover:bg-teal-700 text-white"
                >
                    Deploy store
                </button>
            </div>
        );
    } else if (wiz.deploy.stateId && wiz.deploy.status !== 'complete') {
        content = (
            <div className="flex flex-col items-center py-6">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mb-4"></div>
                <p className="text-gray-700 font-semibold">Deploying store...</p>
                {wiz.deploy.url && (
                    <p className="text-xs text-gray-500 break-all mt-2">URL: {wiz.deploy.url}</p>
                )}
            </div>
        );
    } else if (wiz.deploy.status === 'complete') {
        content = (
            <div className="flex flex-col items-center py-6">
                <div className="text-green-600 text-2xl mb-2">🚀</div>
                <p className="text-green-700 font-semibold mb-2">Store deployed!</p>
                {wiz.deploy.url && (
                    <a
                        href={wiz.deploy.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="mt-2 w-full py-2 rounded-lg font-medium bg-teal-600 hover:bg-teal-700 text-white text-center"
                    >
                        Visit store
                    </a>
                )}
            </div>
        );
    } else if (wiz.fixtures.error || wiz.images.error || wiz.deploy.error || errorMsg) {
        content = (
            <div className="flex flex-col items-center py-6">
                <div className="text-red-600 text-2xl mb-2">✖</div>
                <p className="text-red-700 font-semibold">{wiz.fixtures.error || wiz.images.error || wiz.deploy.error || errorMsg}</p>
                <button
                    onClick={onBeginGeneration}
                    className="mt-4 w-full py-2 rounded-lg font-medium bg-teal-600 hover:bg-teal-700 text-white"
                >
                    Retry
                </button>
            </div>
        );
    }

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
                        {Object.entries(wiz.plugins).map(([composer, version]) => (
                            <li key={composer}>{prettify(composer)} ({version})</li>
                        ))}
                    </ul>
                </div>
                <div className="bg-white rounded-xl shadow p-6 border">
                    <h3 className="font-semibold text-gray-800 mb-2">Deploy:</h3>
                    <p className="text-sm text-gray-700">{wiz.target}{wiz.target === 'platform.sh' && wiz.env ? ` (${wiz.env})` : ''}</p>
                </div>
            </div>
            {content}
        </motion.div>
    );
}
