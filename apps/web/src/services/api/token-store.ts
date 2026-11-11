/**
 * The access token lives in memory only — never in localStorage or cookies —
 * so injected scripts cannot read it from storage. A page reload loses it by
 * design; the session is restored from the HttpOnly refresh cookie.
 */
export interface AccessSession {
  accessToken: string;
  /** Epoch milliseconds. */
  expiresAt: number;
}

type Listener = (session: AccessSession | null) => void;

let current: AccessSession | null = null;
const listeners = new Set<Listener>();

export const tokenStore = {
  get(): AccessSession | null {
    return current;
  },

  set(accessToken: string, expiresInSeconds: number, now: number = Date.now()): void {
    current = { accessToken, expiresAt: now + expiresInSeconds * 1000 };
    listeners.forEach((listener) => listener(current));
  },

  clear(): void {
    current = null;
    listeners.forEach((listener) => listener(null));
  },

  expiresWithin(milliseconds: number, now: number = Date.now()): boolean {
    return current !== null && current.expiresAt - now <= milliseconds;
  },

  subscribe(listener: Listener): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
  },
};
