import React, {useCallback, useContext} from 'react';
import {WizardContext} from '../../hooks/WizardProvider';
import {motion} from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';

// Static map for top-level labels
const LABELS = {
    industry: 'Industry',
    locales: 'Locales',
    currencies: 'Currencies',
    countries: 'Countries',
    categories: 'Categories',
    productsPerCat: 'Products per Category',
    descriptionStyle: 'Description Style',
    imageStyle: 'Image Style',
    themePreferences: 'Theme Preferences',
};

// Utility to prettify camelCase or snake_case into Title Case
function prettifyKey(key) {
    return key
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
}

// Dynamic converter for general codes
function mapCode(code) {
    return code
        .split(/[_\s]+/)
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

export default function InterviewSummaryStep() {
    const {wiz, dispatch} = useContext(WizardContext);

    const back = useCallback(() => {
        dispatch({ type: 'SET_STEP', step: Math.max(wiz.step - 1, 1), direction: -1 });
    }, [wiz.step, dispatch]);

    function renderValue(val, parentKey) {
        if (Array.isArray(val)) {
            // Special handling for translations as array of [text, locale]
            if (parentKey === 'translations') {
                return (
                    <ul className="pl-4 list-disc">
                        {val.map(([text, loc], idx) => (
                            <li key={idx} className="mb-1">
                                {text} ({loc})
                            </li>
                        ))}
                    </ul>
                );
            }
            // For locales array, show only region code (after underscore)
            if (parentKey === 'locales') {
                return (
                    <ul className="pl-4 list-disc">
                        {val.map((item, idx) => {
                            const parts = String(item).split('_');
                            const region = parts[1] ? parts[1].toUpperCase() : item.toUpperCase();
                            return <li key={idx} className="mb-1">{region}</li>;
                        })}
                    </ul>
                );
            }
            // For currencies and countries, show raw values
            if (['currencies', 'countries'].includes(parentKey)) {
                return (
                    <ul className="pl-4 list-disc">
                        {val.map((item, idx) => (
                            <li key={idx} className="mb-1">{item}</li>
                        ))}
                    </ul>
                );
            }
            // Fallback array handling
            return (
                <ul className="pl-4 list-disc">
                    {val.map((item, idx) => (
                        <li key={idx} className="mb-1">
                            {typeof item === 'object' ? renderObject(item) : mapCode(item)}
                        </li>
                    ))}
                </ul>
            );
        }
        if (typeof val === 'object' && val !== null) {
            return renderObject(val);
        }
        // Primitive values
        if (parentKey === 'code') {
            return mapCode(val);
        }
        return val;
    }

    function renderObject(obj) {
        return (
            <div className="space-y-3">
                {Object.entries(obj).map(([key, value]) => {
                    // Skip referenceBrandUrl
                    if (key === 'referenceBrandUrl') return null;

                    const label = LABELS[key] || prettifyKey(key);
                    return (
                        <div key={key} className="flex items-start">
                            <strong className="w-40 text-sm font-medium text-gray-700">{label}:</strong>
                            <div className="ml-2 text-sm text-gray-800">
                                {renderValue(value, key)}
                            </div>
                        </div>
                    );
                })}
            </div>
        );
    }

    return (
        <motion.div
            key="3"
            custom={wiz.direction}
            variants={wizardStepVariants}
            initial="enter"
            animate="center"
            exit="exit"
            transition={{ duration: 0.25, type: 'tween', ease: 'easeInOut' }}
            className="p-6"
        >
            <div className="flex justify-center mb-6">
                <button
                    onClick={() => dispatch({ type: 'NEXT_STEP' })}
                    className="w-full max-w-md py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl font-semibold shadow transition-transform transform hover:scale-105"
                >
                    Next →
                </button>
            </div>

            <div className="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow">
                {renderObject(wiz.storeDetails)}
            </div>

            <div className="flex justify-between mt-6">
                <button onClick={back} className="text-teal-600 hover:underline text-sm">
                    ← Back
                </button>
            </div>
        </motion.div>
    );
}
