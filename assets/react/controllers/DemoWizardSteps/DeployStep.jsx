import React, { useContext, useState } from 'react';
import { WizardContext } from '../../hooks/WizardProvider';
import { motion } from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';

const DEPLOYERS = [
  { value: 'localhost', label: 'Localhost' },
  { value: 'platformsh', label: 'Platform.sh' },
];

export default function DeployStep() {
  const { wiz, dispatch } = useContext(WizardContext);
  const [selectedDeployer, setSelectedDeployer] = useState(wiz.target || 'localhost');
  const [localhostPort, setLocalhostPort] = useState(wiz.env || '8000');
  const [localhostStatus, setLocalhostStatus] = useState(null);
  const [checking, setChecking] = useState(false);
  const [platformEnv, setPlatformEnv] = useState(wiz.env || 'main');
  const [platformStatus, setPlatformStatus] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);
  const [imagesSuccess, setImagesSuccess] = useState(false);
  const [imagesError, setImagesError] = useState(null);

  // Pobierz presetId z globalnego stanu (np. z wiz lub propsów, tu uproszczone)
  const presetId = wiz.presetId

  const handleDeployerChange = (e) => {
    const value = e.target.value;
    setSelectedDeployer(value);
    dispatch({ type: 'SET_WIZARD_STATE', state: { target: value } });
  };

  // Localhost checker
  const checkLocalhost = async () => {
    setChecking(true);
    setLocalhostStatus(null);
    try {
      const res = await fetch(`https://localhost:${localhostPort}/store-assembler`);
      if (res.ok) {
        setLocalhostStatus('ok');
      } else {
        setLocalhostStatus('error');
      }
    } catch (err) {
      setLocalhostStatus('error');
      console.warn('Fetch error:', err);
      console.log('Jeśli w network tab widzisz 200, to prawdopodobnie CORS. W innym przypadku apka nie działa na tym porcie.');
    } finally {
      setChecking(false);
      console.log('Localhost check completed');
    }
  };

  // Platform.sh checker (placeholder)
  const checkPlatform = async () => {
    setChecking(true);
    setPlatformStatus(null);
    try {
      const url = `https://${platformEnv}.platformsh.site/api/ping`;
      const res = await fetch(url);
      if (res.ok) {
        setPlatformStatus('ok');
      } else {
        setPlatformStatus('error');
      }
    } catch {
      setPlatformStatus('error');
    } finally {
      setChecking(false);
    }
  };

  // Sekwencja: generate fixtures -> generate images
  const handleGenerateAndDeploy = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setSuccess(false);
    setImagesSuccess(false);
    setImagesError(null);
    try {
      // 1. Ustaw deployer w stanie
      if (selectedDeployer === 'localhost') {
        dispatch({ type: 'SET_WIZARD_STATE', state: { target: 'localhost', env: localhostPort } });
      } else if (selectedDeployer === 'platformsh') {
        dispatch({ type: 'SET_WIZARD_STATE', state: { target: 'platformsh', env: platformEnv } });
      }
      // 2. Generate fixtures
      const fixturesRes = await fetch(`/api/store-presets/${encodeURIComponent(presetId)}/fixtures-generate`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ storeDetails: wiz.storeDetails || {} }),
      });
      if (!fixturesRes.ok) {
        const errText = await fixturesRes.text();
        throw new Error('Fixtures error: ' + errText);
      }
      setSuccess(true);
      // 3. Generate images
      const imagesRes = await fetch(`/api/store-presets/${encodeURIComponent(presetId)}/generate-images`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({}),
      });
      if (!imagesRes.ok) {
        const errText = await imagesRes.text();
        setImagesError('Images error: ' + errText);
        return;
      }
      setImagesSuccess(true);
    } catch (err) {
      setError(err.message || 'Unknown error');
    } finally {
      setLoading(false);
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
      transition={{ duration: 0.25, type: 'tween', ease: 'easeInOut' }}
    >
      <h2 className="text-2xl font-bold mb-6 text-center text-teal-700">Generate & Deploy</h2>
      <form onSubmit={handleGenerateAndDeploy} className="max-w-lg mx-auto flex flex-col gap-8 bg-white rounded-xl shadow-lg p-8 border border-gray-100">
        <div>
          <label className="block font-medium mb-2">Deployment method:</label>
          <select
            value={selectedDeployer}
            onChange={handleDeployerChange}
            className="w-full border rounded px-3 py-2"
            disabled={loading}
          >
            {DEPLOYERS.map(d => (
              <option key={d.value} value={d.value}>{d.label}</option>
            ))}
          </select>
        </div>

        {selectedDeployer === 'localhost' && (
          <div className="space-y-2">
            <label className="block font-medium">Localhost port:</label>
            <input
              type="number"
              min="1"
              max="65535"
              value={localhostPort}
              onChange={e => setLocalhostPort(e.target.value)}
              className="border rounded px-3 py-2 w-32"
              disabled={loading}
            />
            <button
              type="button"
              onClick={checkLocalhost}
              disabled={checking || loading}
              className="ml-4 px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700"
            >
              {checking ? 'Checking...' : 'Check connection'}
            </button>
            {localhostStatus === 'ok' && <span className="ml-2 text-green-600">Sylius detected!</span>}
            {localhostStatus === 'cors-ok' && <span className="ml-2 text-yellow-600">Sylius prawdopodobnie działa (CORS blokuje odpowiedź, sprawdź network tab)</span>}
            {localhostStatus === 'error' && <span className="ml-2 text-red-600">No Sylius app detected or connection refused</span>}
          </div>
        )}

        {selectedDeployer === 'platformsh' && (
          <div className="space-y-2">
            <label className="block font-medium">Platform.sh env (subdomain):</label>
            <input
              type="text"
              value={platformEnv}
              onChange={e => setPlatformEnv(e.target.value)}
              className="border rounded px-3 py-2 w-64"
              placeholder="main-bvxea6i-6gbneqsqpqxka.de-2"
              disabled={loading}
            />
            <button
              type="button"
              onClick={checkPlatform}
              disabled={checking || loading}
              className="ml-4 px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700"
            >
              {checking ? 'Checking...' : 'Check connection'}
            </button>
            {platformStatus === 'ok' && <span className="ml-2 text-green-600">Sylius detected!</span>}
            {platformStatus === 'error' && <span className="ml-2 text-red-600">No Sylius app detected</span>}
          </div>
        )}

        {error && <div className="p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">{error}</div>}
        {success && <div className="p-3 bg-green-50 border border-green-200 rounded text-green-700 text-sm">Fixtures generated successfully!</div>}
        {imagesError && <div className="p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">{imagesError}</div>}
        {imagesSuccess && <div className="p-3 bg-green-50 border border-green-200 rounded text-green-700 text-sm">Images generated successfully! Ready to deploy.</div>}

        <div>
          <button
            type="submit"
            className="w-full py-4 bg-green-600 text-white rounded-xl font-bold text-lg shadow-lg hover:bg-green-700 transition flex items-center justify-center gap-2"
            disabled={loading}
          >
            {loading && <span className="animate-spin h-5 w-5 border-b-2 border-white rounded-full mr-2"></span>}
            Generate & Deploy
          </button>
        </div>
      </form>
    </motion.div>
  );
}
