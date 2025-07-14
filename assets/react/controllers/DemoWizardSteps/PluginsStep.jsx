import React, {useContext} from 'react';
import {WizardContext} from '../../hooks/WizardProvider';
import {motion} from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';
import {useSupportedPlugins} from "../../hooks/useSupportedPlugins";
import {useStorePreset} from '../../hooks/useStorePreset';

export default function PluginsStep() {
    const {wiz, dispatch} = useContext(WizardContext);
    const {plugins, loading: pluginsLoading, error: pluginsError, refetch} = useSupportedPlugins();
    const {updatePreset, getPreset} = useStorePreset();

    const prettify = (name) => {
        return name
            .replace(/^sylius\//, '')
            .replace(/-plugin$/, '')
            .replace(/-/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    }

    const handlePluginsSelected = (plugins) => {
        dispatch({type: 'SET_SELECTED_PLUGINS', selectedPlugins: plugins});
        updatePreset({selectedPlugins: plugins})
            .then(() => getPreset())
            .catch(err => console.error('Failed to update preset:', err));
    };

    return (
        <motion.div
            key="1"
            custom={wiz.direction}
            variants={wizardStepVariants}
            initial="enter"
            animate="center"
            exit="exit"
            transition={{duration: 0.25, type: 'tween', ease: 'easeInOut'}}
        >
            <div className="flex flex-col items-center justify-start w-full pt-6"
                 style={{minHeight: '60vh'}}>
                <div className="w-full max-w-lg">
                    {pluginsLoading ? (
                        <div className="flex flex-col items-center justify-center py-8">
                            <div
                                className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mb-4"></div>
                            <p className="text-gray-600">Loading plugins...</p>
                        </div>
                    ) : pluginsError ? (
                        <div className="text-red-600 mb-4 p-4 bg-red-50 rounded-lg">
                            <p className="font-medium">Failed to load plugins:</p>
                            <p className="text-sm">{pluginsError}</p>
                            <button
                                onClick={() => refetch()}
                                className="mt-2 text-blue-600 underline text-sm hover:text-blue-800"
                            >
                                Try again
                            </button>
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-1 gap-2 mb-6 overflow-y-auto"
                                 style={{maxHeight: 360}}>
                                {plugins.map(p => (
                                    <label key={p.composer} className="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            value={p.composer}
                                            checked={wiz.selectedPlugins.includes(p.composer)}
                                            onChange={e => {
                                                const c = e.target.value;
                                                let updated;
                                                if (wiz.selectedPlugins.includes(c)) {
                                                    updated = wiz.selectedPlugins.filter(x => x !== c);
                                                } else {
                                                    updated = [...wiz.selectedPlugins, c];
                                                }
                                                handlePluginsSelected(updated);
                                            }}
                                            className="h-4 w-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
                                        />
                                        <span className="text-gray-800 text-sm">{prettify(p.name)} ({p.version})</span>
                                    </label>
                                ))}
                            </div>
                            <button
                                onClick={() => {
                                    dispatch({type: 'NEXT_STEP'})
                                }}
                                disabled={pluginsLoading}
                                className={`w-full py-2 rounded-lg font-medium transition ${
                                    pluginsLoading
                                        ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                        : 'bg-teal-600 hover:bg-teal-700 text-white'
                                }`}
                            >
                                {pluginsLoading ? 'Loading plugins...' : 'Next →'}
                            </button>
                        </>
                    )}
                </div>
            </div>
        </motion.div>
    );
}
