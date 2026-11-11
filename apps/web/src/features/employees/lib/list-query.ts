import { useCallback, useMemo } from 'react';
import { useSearchParams } from 'react-router';
import type { EmployeeListQuery, EmployeeRecordState, EmployeeSort, EmployeeSortField } from '@/services/employee-service';
import { EmployeeStatus, EmploymentType } from '@/types/api';
import { RECORD_STATES } from './labels';

export const DEFAULT_PER_PAGE = 25;
const SORT_FIELDS: readonly EmployeeSortField[] = ['employee_number', 'first_name', 'last_name', 'hire_date', 'created_at', 'department'];

/** URL parameter ↔ text filter of the query. Short names keep shared links readable. */
const TEXT_PARAMS = {
  q: 'search',
  name: 'name',
  number: 'employeeNumber',
  email: 'email',
  title: 'jobTitle',
} as const satisfies Record<string, keyof EmployeeListQuery>;

type TextParam = keyof typeof TEXT_PARAMS;

const isStatus = (value: string): value is EmployeeStatus => (Object.values(EmployeeStatus) as string[]).includes(value);
const isEmploymentType = (value: string): value is EmploymentType => (Object.values(EmploymentType) as string[]).includes(value);
const isSortField = (value: string): value is EmployeeSortField => (SORT_FIELDS as string[]).includes(value);
const isRecordState = (value: string | null): value is EmployeeRecordState => (RECORD_STATES as (string | null)[]).includes(value);

function csv(value: string | null): string[] {
  return value ? value.split(',').filter(Boolean) : [];
}

export function parseListQuery(params: URLSearchParams): EmployeeListQuery {
  const sortParam = params.get('sort');
  const sortField = sortParam?.replace(/^-/, '') ?? '';
  const sort: EmployeeSort[] | undefined =
    sortParam && isSortField(sortField) ? [{ field: sortField, direction: sortParam.startsWith('-') ? 'desc' : 'asc' }] : undefined;
  const state = params.get('state');
  const query: EmployeeListQuery = {
    statuses: csv(params.get('status')).filter(isStatus),
    employmentTypes: csv(params.get('type')).filter(isEmploymentType),
    departmentId: params.get('department') ?? undefined,
    recordState: isRecordState(state) ? state : 'active',
    sort,
    perPage: Number(params.get('per_page')) || DEFAULT_PER_PAGE,
    cursor: params.get('cursor') ?? undefined,
  };

  for (const [param, key] of Object.entries(TEXT_PARAMS) as [TextParam, (typeof TEXT_PARAMS)[TextParam]][]) {
    query[key] = params.get(param) ?? undefined;
  }

  return query;
}

export function serializeListQuery(query: EmployeeListQuery): URLSearchParams {
  const params = new URLSearchParams();
  const firstSort = query.sort?.[0];

  for (const [param, key] of Object.entries(TEXT_PARAMS) as [TextParam, (typeof TEXT_PARAMS)[TextParam]][]) {
    const value = query[key];
    if (value) params.set(param, value);
  }

  if (query.statuses?.length) params.set('status', query.statuses.join(','));
  if (query.employmentTypes?.length) params.set('type', query.employmentTypes.join(','));
  if (query.departmentId) params.set('department', query.departmentId);
  if (query.recordState && query.recordState !== 'active') params.set('state', query.recordState);
  if (firstSort) params.set('sort', `${firstSort.direction === 'desc' ? '-' : ''}${firstSort.field}`);
  if (query.perPage && query.perPage !== DEFAULT_PER_PAGE) params.set('per_page', String(query.perPage));
  if (query.cursor) params.set('cursor', query.cursor);

  return params;
}

/** Number of filters (not search, sort or paging) the query applies; shown on the filter button. */
export function activeFilterCount(query: EmployeeListQuery): number {
  return [
    query.name,
    query.employeeNumber,
    query.email,
    query.jobTitle,
    query.departmentId,
    query.statuses?.length,
    query.employmentTypes?.length,
    query.recordState !== 'active' ? query.recordState : undefined,
  ].filter(Boolean).length;
}

/** The query without paging: two queries with the same filter key show the same result set. */
export function filterKey(query: EmployeeListQuery): string {
  const params = serializeListQuery({ ...query, cursor: undefined, perPage: undefined, sort: undefined });

  return params.toString();
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
