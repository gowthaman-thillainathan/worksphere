import { describe, expect, it } from 'vitest';
import { toListEmployeesParams } from '@/services/employee-service';
import { activeFilterCount, filterKey, parseListQuery, serializeListQuery } from './list-query';

describe('employee list URL state', () => {
  it('round-trips search, filters, sort and cursor through the URL', () => {
    const query = parseListQuery(
      new URLSearchParams('q=nim&name=per&number=0042&email=acme&title=engineer&status=active,on_leave&type=contract&department=d1&state=deleted&sort=-department&per_page=50&cursor=abc'),
    );

    expect(query).toEqual({
      search: 'nim',
      name: 'per',
      employeeNumber: '0042',
      email: 'acme',
      jobTitle: 'engineer',
      statuses: ['active', 'on_leave'],
      employmentTypes: ['contract'],
      departmentId: 'd1',
      recordState: 'deleted',
      sort: [{ field: 'department', direction: 'desc' }],
      perPage: 50,
      cursor: 'abc',
    });
    expect(parseListQuery(serializeListQuery(query))).toEqual(query);
  });

  it('ignores values the API would reject', () => {
    expect(parseListQuery(new URLSearchParams('status=active,retired&sort=salary&state=archived'))).toMatchObject({
      statuses: ['active'],
      sort: undefined,
      recordState: 'active',
    });
  });

  it('counts applied filters and ignores paging when comparing result sets', () => {
    const query = parseListQuery(new URLSearchParams('name=per&status=active&state=all&cursor=abc'));

    expect(activeFilterCount(query)).toBe(3);
    expect(filterKey(query)).toBe(filterKey({ ...query, cursor: undefined, perPage: 100 }));
  });

  it('translates the query into the API parameters', () => {
    expect(
      toListEmployeesParams({
        search: 'nim',
        jobTitle: 'lead',
        statuses: ['active', 'notice'],
        recordState: 'all',
        sort: [{ field: 'last_name', direction: 'asc' }],
        cursor: 'c1',
      }),
    ).toEqual({ q: 'nim', 'filter[job_title]': 'lead', 'filter[status]': 'active,notice', 'filter[trashed]': 'with', sort: 'last_name', cursor: 'c1' });
  });
});
