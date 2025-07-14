import React, {useContext} from 'react';
import {WizardContext} from '../../hooks/WizardProvider';
import {motion} from 'framer-motion';
import wizardStepVariants from './wizardStepVariants';

export default function DeployStep() {
    const {wiz} = useContext(WizardContext);


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
            zaimplementuj
        </motion.div>
    );
}
