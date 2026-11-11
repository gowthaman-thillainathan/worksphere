import { describe, expect, it } from 'vitest';
import { employee } from '@/test/msw/fixtures';
import { rebase, theirChanges, yourChanges } from './edit-conflict';
import { employeeToForm } from './employee-form-schema';

const base = employeeToForm(employee({ job_title: 'Engineer', status: 'active', last_name: 'Perera' }));

describe('edit conflicts', () => {
  const mine = { ...base, job_title: 'Senior Engineer', last_name: 'Perera-Silva' };
  const latest = { ...base, job_title: 'Lead Engineer', status: 'on_leave' as const };

  it('lists what others changed and flags overlapping edits', () => {
    expect(yourChanges(base, mine)).toEqual(['last_name', 'job_title']);
    expect(theirChanges(base, latest, mine)).toEqual([
      { field: 'job_title', theirs: 'Lead Engineer', yours: 'Senior Engineer' },
      { field: 'status', theirs: 'on_leave' },
    ]);
  });

  it('applies your changes on top of the latest version', () => {
    expect(rebase(base, latest, mine)).toEqual({ ...latest, job_title: 'Senior Engineer', last_name: 'Perera-Silva' });
  });
});
