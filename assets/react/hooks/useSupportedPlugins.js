import { useState, useEffect } from 'react';

const CACHE_KEY = 'supported_plugins_cache';
const CACHE_TTL = 60 * 60 * 1000; // 1 hour in milliseconds

export function useSupportedPlugins() {
    const [plugins, setPlugins] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const loadPlugins = async () => {
            try {
                // Check cache first
                const cached = localStorage.getItem(CACHE_KEY);
                if (cached) {
                    const { data, timestamp } = JSON.parse(cached);
                    if (Date.now() - timestamp < CACHE_TTL) {
                        setPlugins(data);
                        setLoading(false);
                        return;
                    }
                }

                // Fetch fresh data
                const response = await fetch('/api/supported-plugins');
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error || `HTTP ${response.status}: ${response.statusText}`);
                }
                
                // Check if API returned an error in JSON
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Flatten plugins to { name, version, composer } for UI
                const pluginsFlat = [];
                (data.plugins || []).forEach(plugin => {
                    (plugin.versions.length ? plugin.versions : [null]).forEach(version => {
                        pluginsFlat.push({
                            name: plugin.name, // pełna nazwa z sylius/
                            version: version || 'latest',
                            composer: plugin.name // używamy pełnej nazwy jako composer
                        });
                    });
                });

                // Cache the result
                localStorage.setItem(CACHE_KEY, JSON.stringify({
                    data: pluginsFlat,
                    timestamp: Date.now()
                }));

                setPlugins(pluginsFlat);
                setError(null);
            } catch (err) {
                setError(err.message);
                // Don't cache errors
            } finally {
                setLoading(false);
            }
        };

        loadPlugins();
    }, []);

    return { plugins, loading, error };
} 