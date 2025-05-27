// assets/react/controllers/ThemePanel.jsx
import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import presets from '../theme-presets';
import fontPresets from '../font-presets';

// helper to convert hex to "r, g, b"
function hexToRgb(hex) {
    const c = hex.replace('#', '');
    const num = parseInt(c, 16);
    const r = (num >> 16) & 255;
    const g = (num >> 8) & 255;
    const b = num & 255;
    return `${r}, ${g}, ${b}`;
}

export default function ThemePanel({ previewBaseUrl, section }) {
    const initialVars = {
        '--tblr-primary':       '#206bc4',
        '--tblr-btn-bg':        '#206bc4',
        '--tblr-btn-hover-bg':  '#206bc4',

        '--bs-btn-bg':          '#0d6efd',
        '--bs-btn-hover-bg':    '#0b5ed7',

        '--bs-body-bg':         '#ffffff',
        '--bs-text-color':      '#000000',
        '--bs-link-color':      '#0d6efd',
        '--bs-link-color-rgb':  hexToRgb('#0d6efd'),
        '--bs-link-opacity':    '1',
        '--bs-link-hover-color':'#0b5ed7',
        '--bs-link-hover-color-rgb': hexToRgb('#0b5ed7'),

        '--bs-primary':         '#0d6efd',
        '--bs-primary-rgb':     hexToRgb('#0d6efd'),
        '--bs-bg-opacity':      '1',
    };

    const [vars, setVars] = useState(initialVars);
    const [fontSet, setFontSet] = useState('');
    const [logoUrl, setLogoUrl] = useState(null);
    const [bannerUrl, setBannerUrl] = useState(null);
    const [gptPrompt, setGptPrompt] = useState('');
    const [loadingGPT, setLoadingGPT] = useState(false);

    // Debounce postMessage to iframe
    useEffect(() => {
        const id = setTimeout(() => {
            const iframe = document.getElementById('previewFrame');
            iframe.contentWindow.postMessage(
                { type: 'updateAll', vars, logoUrl, bannerUrl },
                '*'
            );
        }, 100);
        return () => clearTimeout(id);
    }, [vars, logoUrl, bannerUrl]);

    // handle GPT suggestions
    const askGPT = async () => {
        if (!gptPrompt) return;
        setLoadingGPT(true);
        const res = await fetch('/api/theme/generate', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ prompt: gptPrompt, baseVars: vars }),
        });
        const { suggestedVars } = await res.json();
        setVars(v => ({ ...v, ...suggestedVars }));
        setLoadingGPT(false);
    };

    // unified handler for color pickers
    const handleColorChange = (name, hex) => {
        setVars(v => ({
            ...v,
            [name]: hex,
            // auto-generate rgb helper
            [name + '-rgb']: hexToRgb(hex),
        }));
    };

    return (
        <div className="p-4 space-y-4 w-72 bg-white overflow-auto">
            <h3 className="text-lg font-semibold">Theme Creator</h3>

            {/* Presets */}
            <label className="block">
                Preset:
                <select
                    className="block w-full mt-1 p-1 border rounded"
                    onChange={e => {
                        const preset = presets[e.target.value] || {};
                        setVars({ ...initialVars, ...preset });
                    }}
                >
                    <option value="">— custom —</option>
                    {Object.keys(presets).map(key => (
                        <option key={key} value={key}>{key}</option>
                    ))}
                </select>
            </label>

            {/* Font Presets */}
            <label className="block">
                Font Style:
                <select
                    className="block w-full mt-1 p-1 border rounded"
                    value={fontSet}
                    onChange={e => {
                        const fp = fontPresets[e.target.value] || {};
                        setFontSet(e.target.value);
                        setVars(v => ({ ...v, ...fp }));
                    }}
                >
                    <option value="">— default —</option>
                    {Object.keys(fontPresets).map(key => (
                        <option key={key} value={key}>{key}</option>
                    ))}
                </select>
            </label>

            {/* GPT prompt */}
            <label className="block">
                Zapytaj GPT:
                <div className="flex space-x-2 mt-1">
                    <input
                        type="text"
                        className="flex-1 p-1 border rounded"
                        value={gptPrompt}
                        onChange={e => setGptPrompt(e.target.value)}
                        placeholder="motyw koparek..."
                    />
                    <button
                        onClick={askGPT}
                        disabled={loadingGPT}
                        className="px-2 bg-teal-600 text-white rounded"
                    >{loadingGPT ? '…' : 'Go'}</button>
                </div>
            </label>

            {/* Color pickers & opacity sliders */}
            {Object.entries(vars)
                .filter(([name]) => !name.endsWith('-rgb'))
                .map(([name, value]) => {
                    const isOpacity = name.endsWith('-opacity');
                    return (
                        <label key={name} className="flex items-center space-x-2">
                            <span className="text-sm truncate w-28">{name}</span>
                            {isOpacity ? (
                                <input
                                    type="range"
                                    min="0"
                                    max="1"
                                    step="0.01"
                                    value={value}
                                    onChange={e => setVars(v => ({ ...v, [name]: e.target.value }))}
                                    className="flex-1"
                                />
                            ) : (
                                <input
                                    type="color"
                                    className="w-8 h-8 p-0 border-0"
                                    value={value}
                                    onChange={e => handleColorChange(name, e.target.value)}
                                />
                            )}
                        </label>
                    )})}

            {/* Logo selector */}
            <label className="block">
                Logo:
                <select
                    className="block w-full mt-1 p-1 border rounded"
                    onChange={e => setLogoUrl(e.target.value)}
                >
                    <option value="">— none —</option>
                    <option value="/uploads/logo1.png">Logo 1</option>
                    <option value="/uploads/logo2.png">Logo 2</option>
                </select>
            </label>
            {logoUrl && <img src={logoUrl} className="w-full h-20 object-contain" alt="Logo" />}

            {/* Banner selector */}
            <label className="block">
                Banner:
                <select
                    className="block w-full mt-1 p-1 border rounded"
                    onChange={e => setBannerUrl(e.target.value)}
                >
                    <option value="">— none —</option>
                    <option value="/uploads/banner1.jpg">Banner 1</option>
                    <option value="/uploads/banner2.jpg">Banner 2</option>
                </select>
            </label>
            {bannerUrl && <img src={bannerUrl} className="w-full h-24 object-cover" alt="Banner" />}
        </div>
    );
}
