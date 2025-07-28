import {useState, useEffect, useCallback, useContext} from 'react';
import {WizardContext} from "./WizardProvider";

export function useStorePreset() {
    const [presetId, setPresetId] = useState(() => localStorage.getItem('presetId') || null);
    const [preset, setPreset] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const { wiz, dispatch } = useContext(WizardContext);

    // Create or load presetId on mount
    useEffect(() => {
        if (!presetId) {
            setLoading(true);
            fetch('/api/store-presets', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    setPresetId(data.storePresetId);
                    localStorage.setItem('presetId', data.storePresetId);
                })
                .catch(e => setError(e.message))
                .finally(() => setLoading(false));
        }
    }, [presetId]);

    // Update preset (PATCH)
    const updatePreset = useCallback((data) => {
        if (!presetId) return;
        setLoading(true);
        return fetch(`/api/store-presets/${presetId}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        })
            .finally(() => setLoading(false));
    }, [presetId]);

    // Delete preset
    const deletePreset = useCallback(() => {
        if (!presetId) return;
        setLoading(true);
        fetch(`/api/store-presets/${presetId}`, { method: 'DELETE' })
            .then(() => {
                setPresetId(null);
                setPreset(null);
                localStorage.removeItem('presetId');
            })
            .catch(e => setError(e.message))
            .finally(() => setLoading(false));
    }, [presetId]);

    // Generate store fixtures and images
    const generateStore = useCallback(async (details) => {
        if (!presetId) return;
        setLoading(true);
        try {
            const response = await fetch(
                `/api/store-presets/${encodeURIComponent(presetId)}/generate-store`,
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(details),
                }
            );
            const raw = await response.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch {
                throw new Error(`Invalid JSON response from API: ${raw}`);
            }
            if (data.error) throw new Error(data.error);
            return data;
        } catch (err) {
            setError(err.message);
            dispatch({ type: 'ERROR', error: err.message });
            throw err;
        } finally {
            setLoading(false);
        }
    }, [presetId, dispatch]);

    const deployStore = useCallback(async (env) => {
        if (!presetId) return;
        setLoading(true);
        try {
            const response = await fetch(`/api/store-presets/${presetId}/deploy-store`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ env }),
            });
            const data = await response.json();
            if (data.error) throw new Error(data.error);
            return data;
        } catch (err) {
            setError(err.message);
            dispatch({ type: 'ERROR', error: err.message });
            throw err;
        } finally {
            setLoading(false);
        }
    }, [presetId, dispatch]);

    return {
        presetId,
        preset,
        loading,
        error,
        updatePreset,
        deletePreset,
        setPresetId,
        generateStore,
        deployStore,
    };
}
