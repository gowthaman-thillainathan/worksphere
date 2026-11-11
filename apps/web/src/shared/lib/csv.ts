/**
 * RFC 4180 CSV. Cells that start with a formula character are prefixed with
 * an apostrophe so spreadsheets do not execute them (CSV injection).
 */
const FORMULA_START = /^[=+\-@\t\r]/;
const NEEDS_QUOTES = /[",\r\n]/;
const LINE_BREAK = '\r\n';

export interface CsvColumn<Row> {
  header: string;
  value: (row: Row) => string | number | null | undefined;
}

function cell(value: string | number | null | undefined): string {
  if (value === null || value === undefined) return '';

  let text = String(value);

  if (typeof value === 'string' && FORMULA_START.test(text)) text = `'${text}`;

  return NEEDS_QUOTES.test(text) ? `"${text.replaceAll('"', '""')}"` : text;
}

export function toCsv<Row>(rows: readonly Row[], columns: readonly CsvColumn<Row>[]): string {
  const lines = [columns.map((column) => cell(column.header)), ...rows.map((row) => columns.map((column) => cell(column.value(row))))];

  return lines.map((line) => line.join(',')).join(LINE_BREAK) + LINE_BREAK;
}
