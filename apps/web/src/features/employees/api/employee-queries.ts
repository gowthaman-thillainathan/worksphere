import { keepPreviousData, useInfiniteQuery, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query';
import { useCallback, useEffect } from 'react';
import { AuditService, type AuditQuery } from '@/services/audit-service';
import { DocumentService } from '@/services/document-service';
import { EmployeeService, type EmployeeListQuery } from '@/services/employee-service';
import { queryKeys } from '@/shared/lib/query-keys';
import type { Employee, ListEmployees200 } from '@/types/api';

/** Reads of the employee module. Writes live in employee-mutations.ts. */

const HISTORY_PAGE_SIZE = 20;
const RECENT_ACTIVITY_SIZE = 5;
/** Prefetched details are fresh enough to show without a spinner for this long. */
const PREFETCH_STALE_TIME = 30_000;
const SUBJECT_TYPE = 'employee';

interface ListOptions {
  enabled?: boolean;
  /** Fetch the next page in the background so "Next" feels instant. */
  prefetchNext?: boolean;
}

function listQueryOptions(query: EmployeeListQuery) {
  return {
    queryKey: queryKeys.employees.list(query),
    queryFn: ({ signal }: { signal: AbortSignal }) => EmployeeService.list(query, signal),
  };
}

export function useEmployees(query: EmployeeListQuery, { enabled = true, prefetchNext = false }: ListOptions = {}) {
  const queryClient = useQueryClient();
  const result = useQuery({
    ...listQueryOptions(query),
    placeholderData: keepPreviousData, // keep the table steady while the next page loads
    enabled,
  });
  const nextCursor = result.data?.meta.next_cursor;

  useEffect(() => {
    if (prefetchNext && nextCursor) {
      void queryClient.prefetchQuery(listQueryOptions({ ...query, cursor: nextCursor }));
    }
  }, [prefetchNext, nextCursor, query, queryClient]);

  return result;
}

/** An employee already shown in a cached directory page, to render the profile instantly. */
function fromCachedLists(queryClient: QueryClient, id: string): Employee | undefined {
  for (const [, page] of queryClient.getQueriesData<ListEmployees200>({ queryKey: queryKeys.employees.lists() })) {
    const match = page?.data.find((employee) => employee.id === id && employee.deleted_at === null);

    if (match) return match;
  }

  return undefined;
}

export function useEmployee(id: string) {
  const queryClient = useQueryClient();

  return useQuery<Employee>({
    queryKey: queryKeys.employees.detail(id),
    queryFn: ({ signal }) => EmployeeService.get(id, signal),
    placeholderData: () => fromCachedLists(queryClient, id),
  });
}

/** Loads a profile on hover or focus, before the click. */
export function usePrefetchEmployee() {
  const queryClient = useQueryClient();

  return useCallback(
    (id: string) =>
      void queryClient.prefetchQuery({
        queryKey: queryKeys.employees.detail(id),
        queryFn: ({ signal }) => EmployeeService.get(id, signal),
        staleTime: PREFETCH_STALE_TIME,
      }),
    [queryClient],
  );
}

/** Fetches the current version from the API, bypassing the cache (e.g. after a 412). */
export function useFetchLatestEmployee() {
  const queryClient = useQueryClient();

  return useCallback(
    (id: string) => queryClient.fetchQuery({ queryKey: queryKeys.employees.detail(id), queryFn: ({ signal }) => EmployeeService.get(id, signal), staleTime: 0 }),
    [queryClient],
  );
}

export function useHeadcount(enabled = true) {
  return useQuery({
    queryKey: queryKeys.employees.headcount(),
    queryFn: ({ signal }) => EmployeeService.headcount(signal),
    enabled,
  });
}

/** Photos need the bearer token, so they are fetched as blobs (not <img src>). */
export function useEmployeePhoto(employee: Pick<Employee, 'id' | 'photo_url' | 'meta'>) {
  return useQuery({
    queryKey: queryKeys.employees.photo(employee.id, employee.meta.version),
    queryFn: ({ signal }) => EmployeeService.photo(employee.id, signal),
    enabled: Boolean(employee.photo_url),
    staleTime: Infinity, // keyed by version: a new photo means a new key
  });
}

/** The full history, newest first, loaded page by page. */
export function useEmployeeHistory(id: string, enabled: boolean) {
  return useInfiniteQuery({
    queryKey: queryKeys.audit.subject(SUBJECT_TYPE, id),
    queryFn: ({ signal, pageParam }) => AuditService.list({ subjectType: SUBJECT_TYPE, subjectId: id, perPage: HISTORY_PAGE_SIZE, cursor: pageParam }, signal),
    initialPageParam: undefined as string | undefined,
    getNextPageParam: (page) => page.meta.next_cursor ?? undefined,
    enabled,
  });
}

export function useRecentActivity(id: string, enabled: boolean) {
  const query: AuditQuery = { subjectType: SUBJECT_TYPE, subjectId: id, perPage: RECENT_ACTIVITY_SIZE };

  return useQuery({
    queryKey: queryKeys.audit.list(query),
    queryFn: ({ signal }) => AuditService.list(query, signal),
    enabled,
  });
}

export function useEmployeeAccount(id: string, enabled: boolean) {
  return useQuery({
    queryKey: queryKeys.employees.account(id),
    queryFn: ({ signal }) => EmployeeService.account(id, signal),
    enabled,
  });
}

export function useEmployeeDocuments(id: string, enabled: boolean) {
  return useQuery({
    queryKey: queryKeys.employees.documents(id),
    queryFn: ({ signal }) => DocumentService.list(id, signal),
    enabled,
  });
}
