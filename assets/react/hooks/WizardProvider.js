import React, { createContext } from 'react';
import {useWizardState} from "./useWizardState";

export const WizardContext = createContext(null);

export function WizardProvider({ children }) {
    const [wiz, dispatch] = useWizardState();
    return (
        <WizardContext.Provider value={{ wiz, dispatch }}>
            {children}
        </WizardContext.Provider>
    );
}
