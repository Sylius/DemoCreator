import React, { createContext } from 'react';
import {useWizardState} from "./useWizardState";
import {useStorePreset} from "./useStorePreset";

export const WizardContext = createContext(null);
export const StorePresetContext = createContext(null);

export function WizardProvider({ children }) {
    const [wiz, dispatch] = useWizardState();
    const storePreset = useStorePreset();
    return (
        <WizardContext.Provider value={{ wiz, dispatch }}>
            <StorePresetContext.Provider value={storePreset}>
                {children}
            </StorePresetContext.Provider>
        </WizardContext.Provider>
    );
}
