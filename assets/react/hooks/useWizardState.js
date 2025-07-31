import {useReducer, useEffect} from 'react';

const initialState = {
    step: 1,
    direction: 1,
    plugins: {},
    target: '',
    env: '',
    state: 'collecting',
    storeDetails: null,
    error: null,
};

function reducer(state, action) {
    switch (action.type) {
        // ── navigation & data ─────────────────────────────────────────────────────
        case 'SET_STEP':
            return {...state, step: action.step, direction: action.direction};

        case 'SET_SELECTED_PLUGINS':
            return {...state, plugins: action.plugins};

        case 'UPDATE_STORE_DETAILS':
            return {...state, storeDetails: action.storeDetails};

        case 'NEXT_STEP':
            return {...state, step: state.step + 1, direction: 1};

        case 'SET_WIZARD_STATE':
            // merge in any fields plus optionally override `state`
            return {...state, ...action.state};

        // ── high‑level flow transitions ──────────────────────────────────────────
        // you can name these as you like—here’s a suggestion:
        case 'BEGIN_GENERATION':
            return {...state, state: 'generating', error: null};

        case 'READY_TO_DEPLOY':
            return {...state, state: 'readyToDeploy', error: null};

        case 'BEGIN_DEPLOY':
            return {...state, state: 'deploying', error: null};

        case 'COMPLETE':
            return {...state, state: 'complete', error: null};

        case 'ERROR':
            console.log(`Error in wizard: ${JSON.stringify(action)}`);
            return {...state, state: 'error', error: action.error};

        case 'SET_ERROR':
            return {...state, error: action.error};

        case 'RESET_WIZARD':
            localStorage.clear();
            return initialState;

        default:
            return state;
    }
}

export function useWizardState() {
    const [wizard, dispatch] = useReducer(
        reducer,
        initialState,
        init => {
            try {
                const saved = window.localStorage.getItem('wizardState');
                return saved ? JSON.parse(saved) : init;
            } catch {
                return init;
            }
        }
    );

    useEffect(() => {
        window.localStorage.setItem('wizardState', JSON.stringify(wizard));
    }, [wizard]);

    return [wizard, dispatch];
}
