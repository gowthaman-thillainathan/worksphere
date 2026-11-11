import type { AxiosRequestConfig } from 'axios';

/** Fraction of the upload sent so far, 0–1. */
export type UploadProgressListener = (fraction: number) => void;

/** Request options that report upload progress to the listener. */
export function withUploadProgress(onProgress?: UploadProgressListener): AxiosRequestConfig {
  return {
    onUploadProgress: (event) => {
      if (event.total) onProgress?.(event.loaded / event.total);
    },
  };
}
