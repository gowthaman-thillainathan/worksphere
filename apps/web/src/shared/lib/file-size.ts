const UNITS = ['B', 'KB', 'MB', 'GB'] as const;
const STEP = 1024;

/** 1536 → "1.5 KB". */
export function formatFileSize(bytes: number): string {
  let value = bytes;
  let unit = 0;

  while (value >= STEP && unit < UNITS.length - 1) {
    value /= STEP;
    unit += 1;
  }

  return `${unit === 0 ? value : value.toFixed(1)} ${UNITS[unit]}`;
}
