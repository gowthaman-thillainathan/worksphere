import { describe, expect, it } from 'vitest';
import { employee } from '@/test/msw/fixtures';
import { changedFields, emptyEmployeeForm, employeeFormSchema, employeeToForm, formToPayload } from './employee-form-schema';

const valid = { ...emptyEmployeeForm(), first_name: 'Nimal', last_name: 'Perera', work_email: 'nimal@meridian.test', job_title: 'Engineer', hire_date: '2022-04-01' };

describe('employee form rules', () => {
  it('accepts a complete form', () => {
    expect(employeeFormSchema.safeParse(valid).success).toBe(true);
  });

  it('requires a termination date for terminated employees, not before the hire date', () => {
    const missing = employeeFormSchema.safeParse({ ...valid, status: 'terminated' });
    const early = employeeFormSchema.safeParse({ ...valid, status: 'terminated', termination_date: '2021-01-01' });

    expect(missing.error?.issues[0]?.path).toEqual(['termination_date']);
    expect(early.error?.issues[0]?.message).toBe('Cannot be before the hire date.');
  });

  it('turns blanks into nulls and leaves personal data out without the permission', () => {
    const payload = formToPayload({ ...valid, preferred_name: '  ', phone: '+94 77 123 4567' }, false);

    expect(payload.preferred_name).toBeNull();
    expect(payload).not.toHaveProperty('phone');
  });

  it('sends only the fields that changed', () => {
    const original = employee({ job_title: 'Engineer' });
    const before = formToPayload(employeeToForm(original), true);
    const after = formToPayload({ ...employeeToForm(original), job_title: 'Lead Engineer' }, true);

    expect(changedFields(before, after)).toEqual({ job_title: 'Lead Engineer' });
  });
});
