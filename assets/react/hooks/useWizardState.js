import {useReducer, useEffect} from 'react';

const initialState = {
    step: 1,
    direction: 1,
    selectedPlugins: [],
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
            return {...state, selectedPlugins: action.selectedPlugins};
        case 'SET_WIZARD_STATE':
            return {...state, ...action.state};
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
        case 'RESET_WIZARD':
            localStorage.removeItem('messages');
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
