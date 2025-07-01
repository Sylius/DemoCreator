import { useState, useEffect, useCallback } from 'react';

export function useStorePreset() {
  const [presetId, setPresetId] = useState(() => localStorage.getItem('presetId') || null);
  const [preset, setPreset] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Create or load presetId on mount
  useEffect(() => {
    if (!presetId) {
      setLoading(true);
      fetch('/api/store-presets', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
          setPresetId(data.presetId);
          localStorage.setItem('presetId', data.presetId);
        })
        .catch(e => setError(e.message))
        .finally(() => setLoading(false));
    }
  }, [presetId]);

  // Fetch preset data
  const getPreset = useCallback(() => {
    if (!presetId) return;
    setLoading(true);
    fetch(`/api/store-presets/${presetId}`)
      .then(res => res.json())
      .then(setPreset)
      .catch(e => setError(e.message))
      .finally(() => setLoading(false));
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
      .then(res => res.json())
      .then(() => getPreset())
      .catch(e => setError(e.message))
      .finally(() => setLoading(false));
  }, [presetId, getPreset]);

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

  return {
    presetId,
    preset,
    loading,
    error,
    getPreset,
    updatePreset,
    deletePreset,
    setPresetId, // for manual override/reset
  };
} 