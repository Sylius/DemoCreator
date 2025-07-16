import {useReducer, useEffect} from 'react';

const initialState = {
    step: 1,
    direction: 1,
    plugins: {},
    target: '',
    env: '',
    fixtures: {
        ready: false,
        generating: false,
        error: null,
    },
    images: {
        ready: false,
        generating: false,
        error: null,
    },
    deploy: {
        stateId: null,
        url: null,
        status: null,
    },
    state: 'collecting',
    storeDetails: null,
    error: null,
};

function reducer(state, action) {
    switch (action.type) {
        case 'SET_STEP':
            return {...state, step: action.step, direction: action.direction};
        case 'SET_SELECTED_PLUGINS':
            return {...state, plugins: action.plugins};
        case 'SET_WIZARD_STATE':
            return {...state, ...action.state};
        case 'NEXT_STEP':
            return {
                ...state,
                step: state.step + 1,
                direction: 1,
            };
        case 'UPDATE_STORE_DETAILS':
            return {
                ...state,
                storeDetails: action.storeDetails,
            };
        case 'START_FIXTURES':
            return {
                ...state,
                fixtures: {ready: false, generating: true, error: null},
                images: {ready: false, generating: false, error: null},
            };
        case 'FIXTURES_SUCCESS':
            return {
                ...state,
                fixtures: {ready: true, generating: false, error: null},
            };
        case 'FIXTURES_ERROR':
            return {
                ...state,
                fixtures: {ready: false, generating: false, error: action.error},
            };
        case 'START_IMAGES':
            return {
                ...state,
                images: {ready: false, generating: true, error: null},
            };
        case 'IMAGES_SUCCESS':
            return {
                ...state,
                images: {ready: true, generating: false, error: null},
            };
        case 'IMAGES_ERROR':
            return {
                ...state,
                images: {ready: false, generating: false, error: action.error},
            };
        case 'DEPLOY_START':
            return {
                ...state,
                deploy: {
                    stateId: null,
                    url: null,
                    status: 'deploying',
                    error: null,
                },
            };
        case 'DEPLOY_INITIATED':
            return {
                ...state,
                deploy: {
                    stateId: action.stateId,
                    url: action.url,
                    status: 'deploying',
                    error: null,
                },
            };
        case 'DEPLOY_ERROR':
            return {
                ...state,
                deploy: {
                    ...state.deploy,
                    error: action.error,
                    status: 'error',
                },
            };
        case 'DEPLOY_COMPLETE':
            return {
                ...state,
                deploy: {
                    ...state.deploy,
                    status: 'complete',
                    error: null,
                },
            };
        case 'RESET_WIZARD':
            localStorage.clear();

            return initialState;
        default:
            return state;
    }
}

export function useWizardState() {
    const [state, dispatch] = useReducer(reducer, initialState, init => {
        try {
            const saved = window.localStorage.getItem('wizardState');
            return saved ? JSON.parse(saved) : init;
        } catch {
            return init;
        }
    });

    // Persist on every change
    useEffect(() => {
        window.localStorage.setItem('wizardState', JSON.stringify(state));
    }, [state]);

    return [state, dispatch];
}
