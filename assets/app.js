import { registerReactControllerComponents } from '@symfony/ux-react';
import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// registerReactControllerComponents(require.context('./react/controllers', true, /\.(j|t)sx?$/));

import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './react/App';

const rootEl = document.getElementById('app-root');
if (rootEl) {
    const root = createRoot(rootEl);
    root.render(<App />);
}
