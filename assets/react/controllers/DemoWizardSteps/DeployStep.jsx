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
    } catch {
      setLocalhostStatus('error');
    } finally {
      setChecking(false);
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

  // Obsługa submit (zatwierdzenie wyboru deployera)
  const handleSubmit = (e) => {
    e.preventDefault();
    if (selectedDeployer === 'localhost') {
      dispatch({ type: 'SET_WIZARD_STATE', state: { target: 'localhost', env: localhostPort } });
    } else if (selectedDeployer === 'platformsh') {
      dispatch({ type: 'SET_WIZARD_STATE', state: { target: 'platformsh', env: platformEnv } });
    }
    // Możesz tu dodać przejście do kolejnego kroku
    dispatch({ type: 'NEXT_STEP' });
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
      <h2 className="text-xl font-semibold mb-4">Choose deployment target</h2>
      <form onSubmit={handleSubmit} className="max-w-lg mx-auto flex flex-col gap-6">
        <div>
          <label className="block font-medium mb-2">Deployment method:</label>
          <select
            value={selectedDeployer}
            onChange={handleDeployerChange}
            className="w-full border rounded px-3 py-2"
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
            />
            <button
              type="button"
              onClick={checkLocalhost}
              disabled={checking}
              className="ml-4 px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700"
            >
              {checking ? 'Checking...' : 'Check connection'}
            </button>
            {localhostStatus === 'ok' && <span className="ml-2 text-green-600">Sylius detected!</span>}
            {localhostStatus === 'error' && <span className="ml-2 text-red-600">No Sylius app detected</span>}
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
            />
            <button
              type="button"
              onClick={checkPlatform}
              disabled={checking}
              className="ml-4 px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700"
            >
              {checking ? 'Checking...' : 'Check connection'}
            </button>
            {platformStatus === 'ok' && <span className="ml-2 text-green-600">Sylius detected!</span>}
            {platformStatus === 'error' && <span className="ml-2 text-red-600">No Sylius app detected</span>}
          </div>
        )}

        <div>
          <button
            type="submit"
            className="w-full py-3 bg-teal-600 text-white rounded-lg font-semibold hover:bg-teal-700 transition"
          >
            Next →
          </button>
        </div>
      </form>
    </motion.div>
  );
}
