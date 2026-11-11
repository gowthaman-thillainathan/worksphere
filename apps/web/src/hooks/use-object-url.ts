import { useEffect, useMemo } from 'react';

/** Creates (and revokes) an object URL for a Blob, e.g. an authenticated image download. */
export function useObjectUrl(blob: Blob | undefined): string | undefined {
  const url = useMemo(() => (blob ? URL.createObjectURL(blob) : undefined), [blob]);

  useEffect(
    () => () => {
      if (url) URL.revokeObjectURL(url);
    },
    [url],
  );

  return url;
}
