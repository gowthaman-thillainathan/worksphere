import { describe, expect, it } from 'vitest';
import { formatDate } from './format';

describe('formatDate', () => {
  it('shows date-only values as written, whatever the time zone', () => {
    expect(formatDate('2026-11-10')).toBe(new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(2026, 10, 10)));
  });

  it('shows timestamps on the local calendar day', () => {
    const late = '2026-11-10T23:30:00Z';

    expect(formatDate(late)).toBe(new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(late)));
  });

  it('shows a dash for missing values', () => {
    expect(formatDate(null)).toBe('—');
  });
});
