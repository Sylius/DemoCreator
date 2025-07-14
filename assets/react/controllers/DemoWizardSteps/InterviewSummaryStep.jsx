import React, { useContext } from 'react';
import { WizardContext } from '../../hooks/WizardProvider';
import { motion } from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';

export default function InterviewSummaryStep() {
  const { wiz, dispatch } = useContext(WizardContext);
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
      <div className="flex justify-center items-center py-6 px-4">
        <button
            onClick={() => {dispatch({ type: 'NEXT_STEP' })}}
            className="w-full max-w-lg py-4 px-8 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl font-semibold shadow-lg transition-all duration-200 transform hover:scale-105 border-2 border-teal-500"
        >
          Next →
        </button>
      </div>
      <pre>{JSON.stringify(wiz.storeDetails, null, 2)}</pre>
    </motion.div>
  );
} 