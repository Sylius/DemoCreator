import React, {useContext} from 'react';
import {WizardContext} from '../../hooks/WizardProvider';
import {motion} from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';
import {useSupportedPlugins} from "../../hooks/useSupportedPlugins";

export default function DeployStep() {
  const {wiz, dispatch} = useContext(WizardContext);
  const {plugins} = useSupportedPlugins();

  const prettify = (name) => {
    return name
        .replace(/^sylius\//, '')
        .replace(/-plugin$/, '')
        .replace(/-/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
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
        <h2 className="text-xl font-semibold mb-4 text-teal-700">5. Summary & Deploy</h2>
        <div className="mb-4">
          <h3 className="font-semibold text-gray-800">Plugins:</h3>
          <ul className="list-disc list-inside text-sm text-gray-700">
            {wiz.selectedPlugins.map(c => {
              console.log('Selected plugin:', c);
              return <li key={prettify(c)}>{prettify(c)}</li>;
            })}
          </ul>
        </div>
        <div className="mb-4">
          <h3 className="font-semibold text-gray-800">Deploy:</h3>
          <p className="text-sm text-gray-700">{wiz.target}{wiz.target === 'platform.sh' && wiz.env ? ` (${wiz.env})` : ''}</p>
        </div>
        <div className="flex justify-center">
          <button
              disabled={wiz.deployStatus !== 'complete'}
              onClick={() => window.open(wiz.deploy.url, '_blank')}
              className={`py-2 px-4 rounded-lg font-medium transition ${
                  wiz.deployStatus === 'complete'
                      ? 'bg-green-600 hover:bg-green-700 text-white'
                      : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200'
              } flex items-center space-x-2 mx-auto`}
          >
            {wiz.deployStatus === 'in_progress' && (
                <svg className="animate-spin h-5 w-5 text-white"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10"
                          stroke="currentColor"
                          strokeWidth="4"/>
                  <path className="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8v8z"/>
                </svg>
            )}
            <span>
                        {wiz.deployStatus === 'in_progress' && 'Deploying...'}
              {wiz.deployStatus === 'complete' && 'Go to demo'}
              {wiz.deployStatus === 'failed' && 'Deploy failed'}
                    </span>
          </button>
        </div>
      </motion.div>
  );
}
