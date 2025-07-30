import React, { createContext } from 'react';
import {useWizardState} from "./useWizardState";
import {useStorePreset} from "./useStorePreset";

export const WizardContext = createContext(null);
export const StorePresetContext = createContext(null);

export function WizardProvider({ children }) {
    const [wiz, dispatch] = useWizardState();
    return (
        <WizardContext.Provider value={{ wiz, dispatch }}>
            <StorePresetProvider>{children}</StorePresetProvider>
        </WizardContext.Provider>
    );
}

function StorePresetProvider({ children }) {
    const storePreset = useStorePreset();
    return (
        <StorePresetContext.Provider value={storePreset}>
            {children}
        </StorePresetContext.Provider>
    );
}
