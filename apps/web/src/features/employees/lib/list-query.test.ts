import { describe, expect, it } from 'vitest';
import { toListEmployeesParams } from '@/services/employee-service';
import { parseListQuery, serializeListQuery } from './list-query';

describe('employee list URL state', () => {
  it('round-trips search, filters, sort and cursor through the URL', () => {
    const query = parseListQuery(new URLSearchParams('q=nim&status=active,on_leave&type=contract&sort=-hire_date&per_page=50&cursor=abc&trashed=only'));

    expect(query).toEqual({
      search: 'nim',
      statuses: ['active', 'on_leave'],
      employmentTypes: ['contract'],
      departmentId: undefined,
      trashed: 'only',
      sort: [{ field: 'hire_date', direction: 'desc' }],
      perPage: 50,
      cursor: 'abc',
    });
    expect(parseListQuery(serializeListQuery(query))).toEqual(query);
  });

  it('ignores values the API would reject', () => {
    expect(parseListQuery(new URLSearchParams('status=active,retired&sort=salary'))).toMatchObject({ statuses: ['active'], sort: undefined });
  });

  it('translates the query into the API parameters', () => {
    expect(
      toListEmployeesParams({ search: 'nim', statuses: ['active', 'notice'], sort: [{ field: 'last_name', direction: 'asc' }], cursor: 'c1' }),
    ).toEqual({ q: 'nim', 'filter[status]': 'active,notice', sort: 'last_name', cursor: 'c1' });
  });
});
