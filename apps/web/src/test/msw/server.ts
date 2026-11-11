import { setupServer } from 'msw/node';
import { sessionHandlers } from './handlers';

/** API mock for tests. Unhandled requests fail the test, so no call goes unnoticed. */
export const server = setupServer(...sessionHandlers());
