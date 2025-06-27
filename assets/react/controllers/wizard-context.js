import { createContext, useContext } from 'react';
export const WizardContext = createContext();
export const useWizard = () => useContext(WizardContext); 