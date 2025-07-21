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
            fetch('/api/store-presets', {method: 'POST'})
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
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data),
        })
    }, [presetId]);

    // Delete preset
    const deletePreset = useCallback(() => {
        if (!presetId) return;
        setLoading(true);
        fetch(`/api/store-presets/${presetId}`, {method: 'DELETE'})
            .then(() => {
                setPresetId(null);
                setPreset(null);
                localStorage.removeItem('presetId');
            })
            .catch(e => setError(e.message))
            .finally(() => setLoading(false));
    }, [presetId]);

    const handleCreateFixtures = async () => {
        const payload = { storeDetails: wiz.storeDetails };
        console.log('Creating fixtures with payload:', payload);
        console.log('Using presetId:', presetId);
        try {
            const response = await fetch(`/api/store-presets/${encodeURIComponent(presetId)}/generate-store-definition`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const rawResponse = await response.text();
            let data;
            try {
                data = JSON.parse(rawResponse);
            } catch (parseError) {
                setError(`Invalid JSON response from API:\n${rawResponse}`);
                dispatch({ type: 'SET_WIZARD_STATE', state: { state: 'error' } });
                return;
            }
            if (data.error) setError(data.error);
            else setError(null);
            // Możesz tu dodać obsługę success, np. dispatch({ type: 'SET_WIZARD_STATE', state: { state: 'fixtures_ready' } })
        } catch (err) {
            setError(err.message || 'Unknown error');
            dispatch({ type: 'SET_WIZARD_STATE', state: { state: 'error' } });
        } finally {
            setLoading(false);
        }
    };

    const handleCreateImages = async (maybePresetId, overrideStoreDetails) => {
        const id = maybePresetId || presetId;
        console.log('handleCreateImages called with presetId:', id);
        if (!id) {
            setError('Brak presetId!');
            return;
        }
        setError(null);
        setLoading(true);
        const payload = overrideStoreDetails ? { storeDetails: overrideStoreDetails } : {};
        try {
            const response = await fetch(`/api/store-presets/${encodeURIComponent(id)}/generate-images`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const rawResponse = await response.text();
            let data;
            try {
                data = JSON.parse(rawResponse);
            } catch (parseError) {
                setError(`Invalid JSON response from API:\n${rawResponse}`);
                return;
            }
            if (data.error) setError(data.error);
            else setError(null);
            // Możesz tu dodać obsługę success
        } catch (err) {
            setError(err.message || 'Unknown error');
        } finally {
            setLoading(false);
        }
    };

    return {
        presetId,
        preset,
        loading,
        error,
        updatePreset,
        deletePreset,
        setPresetId,
        handleCreateFixtures,
        handleCreateImages,
    };
}
