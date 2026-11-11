import { useCallback, useMemo } from 'react';
import { useSearchParams } from 'react-router';
import type { EmployeeListQuery, EmployeeSort, EmployeeSortField } from '@/services/employee-service';
import { EmployeeStatus, EmploymentType } from '@/types/api';

const DEFAULT_PER_PAGE = 25;
const SORT_FIELDS: readonly EmployeeSortField[] = ['employee_number', 'first_name', 'last_name', 'hire_date', 'created_at'];

const isStatus = (value: string): value is EmployeeStatus => (Object.values(EmployeeStatus) as string[]).includes(value);
const isEmploymentType = (value: string): value is EmploymentType => (Object.values(EmploymentType) as string[]).includes(value);
const isSortField = (value: string): value is EmployeeSortField => (SORT_FIELDS as string[]).includes(value);

function csv(value: string | null): string[] {
  return value ? value.split(',').filter(Boolean) : [];
}

export function parseListQuery(params: URLSearchParams): EmployeeListQuery {
  const sortParam = params.get('sort');
  const sortField = sortParam?.replace(/^-/, '') ?? '';
  const sort: EmployeeSort[] | undefined =
    sortParam && isSortField(sortField) ? [{ field: sortField, direction: sortParam.startsWith('-') ? 'desc' : 'asc' }] : undefined;
  const trashed = params.get('trashed');

  return {
    search: params.get('q') ?? undefined,
    statuses: csv(params.get('status')).filter(isStatus),
    employmentTypes: csv(params.get('type')).filter(isEmploymentType),
    departmentId: params.get('department') ?? undefined,
    trashed: trashed === 'only' ? 'only' : undefined,
    sort,
    perPage: Number(params.get('per_page')) || DEFAULT_PER_PAGE,
    cursor: params.get('cursor') ?? undefined,
  };
}

export function serializeListQuery(query: EmployeeListQuery): URLSearchParams {
  const params = new URLSearchParams();
  const firstSort = query.sort?.[0];

  if (query.search) params.set('q', query.search);
  if (query.statuses?.length) params.set('status', query.statuses.join(','));
  if (query.employmentTypes?.length) params.set('type', query.employmentTypes.join(','));
  if (query.departmentId) params.set('department', query.departmentId);
  if (query.trashed) params.set('trashed', query.trashed);
  if (firstSort) params.set('sort', `${firstSort.direction === 'desc' ? '-' : ''}${firstSort.field}`);
  if (query.perPage && query.perPage !== DEFAULT_PER_PAGE) params.set('per_page', String(query.perPage));
  if (query.cursor) params.set('cursor', query.cursor);

  return params;
}

/**
 * The directory query lives in the URL, so filtered views can be bookmarked,
 * shared and restored with the back button. Changing anything but the cursor
 * starts again from the first page.
 */
export function useEmployeeListQuery() {
  const [searchParams, setSearchParams] = useSearchParams();
  const query = useMemo(() => parseListQuery(searchParams), [searchParams]);

  const update = useCallback(
    (changes: Partial<EmployeeListQuery>) => {
      const keepCursor = Object.keys(changes).every((key) => key === 'cursor');
      setSearchParams(serializeListQuery({ ...query, cursor: keepCursor ? query.cursor : undefined, ...changes }));
    },
    [query, setSearchParams],
  );

  return { query, update };
}
