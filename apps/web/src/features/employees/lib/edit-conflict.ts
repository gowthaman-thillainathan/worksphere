import type { EmployeeFormValues } from './employee-form-schema';

type Field = keyof EmployeeFormValues;

export interface FieldDifference {
  field: Field;
  /** Value in the latest version saved by someone else. */
  theirs: EmployeeFormValues[Field];
  /** Your unsaved value, when you changed the same field. */
  yours?: EmployeeFormValues[Field];
}

const same = (a: unknown, b: unknown) => JSON.stringify(a) === JSON.stringify(b);

function fields(values: EmployeeFormValues): Field[] {
  return Object.keys(values) as Field[];
}

/** Fields you changed relative to the version you started editing. */
export function yourChanges(base: EmployeeFormValues, mine: EmployeeFormValues): Field[] {
  return fields(mine).filter((field) => !same(base[field], mine[field]));
}

/**
 * What someone else changed since you started editing, flagging the fields
 * you changed too (real conflicts).
 */
export function theirChanges(base: EmployeeFormValues, latest: EmployeeFormValues, mine: EmployeeFormValues): FieldDifference[] {
  const edited = new Set(yourChanges(base, mine));

  return fields(latest)
    .filter((field) => !same(base[field], latest[field]))
    .map((field) => ({ field, theirs: latest[field], ...(edited.has(field) && !same(mine[field], latest[field]) && { yours: mine[field] }) }));
}

/** The latest version with your changes applied on top. Nothing is saved until you submit again. */
export function rebase(base: EmployeeFormValues, latest: EmployeeFormValues, mine: EmployeeFormValues): EmployeeFormValues {
  const merged = { ...latest };

  for (const field of yourChanges(base, mine)) {
    Object.assign(merged, { [field]: mine[field] });
  }

  return merged;
}
