import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from '@/app/App';
import { installInterceptors } from '@/services/api/interceptors';
import '@/styles/index.css';

installInterceptors();

const root = document.getElementById('root');

if (!root) {
  throw new Error('Root element #root is missing from index.html.');
}

createRoot(root).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
