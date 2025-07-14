import React, { useContext } from 'react';
import { WizardContext } from '../../hooks/WizardProvider';
import { motion } from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';

export default function DeployStep(props) {
  const { wiz, dispatch } = useContext(WizardContext);
  return (
    <motion.div
      key="4"
      custom={wiz.direction}
      variants={wizardStepVariants}
      initial="enter"
      animate="center"
      exit="exit"
      transition={{duration: 0.25, type: 'tween', ease: 'easeInOut'}}
    >
      <h2>Deploy your store</h2>
      {/* TODO: implement deployer selection and configuration UI */}
      <pre>{JSON.stringify(wiz.deploy, null, 2)}</pre>
    </motion.div>
  );
} 