import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterAll, afterEach, beforeAll } from 'vitest';
import { installInterceptors } from '@/services/api/interceptors';
import { tokenStore } from '@/services/api/token-store';
import { server } from './msw/server';
import { resetViewport } from './viewport';

installInterceptors();

// jsdom lacks these browser APIs used by the app and Radix.
resetViewport();
window.scrollTo = () => undefined;
URL.createObjectURL ??= () => 'blob:test';
URL.revokeObjectURL ??= () => undefined;
Element.prototype.hasPointerCapture ??= () => false;
Element.prototype.scrollIntoView ??= () => undefined;
globalThis.ResizeObserver ??= class {
  observe() {}
  unobserve() {}
  disconnect() {}
};

beforeAll(() => server.listen({ onUnhandledRequest: 'error' }));
afterEach(() => {
  cleanup();
  server.resetHandlers();
  tokenStore.clear();
  localStorage.clear();
  resetViewport();
});
afterAll(() => server.close());
