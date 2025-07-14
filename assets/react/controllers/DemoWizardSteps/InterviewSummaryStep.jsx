import React, { useContext } from 'react';
import { WizardContext } from '../../hooks/WizardProvider';
import { motion } from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';

export default function InterviewSummaryStep(props) {
  const { wiz } = useContext(WizardContext);
  return (
    <motion.div
      key="3"
      custom={wiz.direction}
      variants={wizardStepVariants}
      initial="enter"
      animate="center"
      exit="exit"
      transition={{duration: 0.25, type: 'tween', ease: 'easeInOut'}}
    >
      <h2>Interview Summary</h2>
      {/* TODO: implement interview summary UI */}
      <pre>{JSON.stringify(wiz.storeDetails, null, 2)}</pre>
    </motion.div>
  );
} 