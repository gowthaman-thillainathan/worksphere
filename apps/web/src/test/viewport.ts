/**
 * jsdom has no layout, so media queries never match by default (the phone
 * layout). Call emulateDesktopViewport() in a test to render the desktop layout.
 */
const MIN_WIDTH = /\(min-width:\s*(\d+)px\)/;
const DESKTOP_WIDTH = 1280;

const PHONE_WIDTH = 0;

function matchMediaAt(width: number) {
  return (query: string) =>
    ({
      matches: Number(MIN_WIDTH.exec(query)?.[1] ?? Infinity) <= width,
      media: query,
      onchange: null,
      addEventListener: () => undefined,
      removeEventListener: () => undefined,
      addListener: () => undefined,
      removeListener: () => undefined,
      dispatchEvent: () => false,
    }) as MediaQueryList;
}

export function emulateDesktopViewport(): void {
  window.matchMedia = matchMediaAt(DESKTOP_WIDTH);
}

/** The default for every test: no min-width query matches. */
export function resetViewport(): void {
  window.matchMedia = matchMediaAt(PHONE_WIDTH);
}
