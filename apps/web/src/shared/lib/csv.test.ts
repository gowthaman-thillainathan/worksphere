import { describe, expect, it } from 'vitest';
import { toCsv } from './csv';

describe('toCsv', () => {
  const columns = [
    { header: 'Name', value: (row: { name: string; note: string | null }) => row.name },
    { header: 'Note', value: (row: { name: string; note: string | null }) => row.note },
  ];

  it('quotes cells with separators, quotes and line breaks', () => {
    expect(toCsv([{ name: 'Perera, Nimal', note: 'Says "hi"\nthere' }], columns)).toBe('Name,Note\r\n"Perera, Nimal","Says ""hi""\nthere"\r\n');
  });

  it('neutralises spreadsheet formulas and leaves nulls empty', () => {
    expect(toCsv([{ name: '=HYPERLINK("x")', note: null }], columns)).toBe('Name,Note\r\n"\'=HYPERLINK(""x"")",\r\n');
  });
});
