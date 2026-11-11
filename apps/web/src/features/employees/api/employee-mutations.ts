import { useMutation, useQueryClient, type QueryClient, type QueryKey } from '@tanstack/react-query';
import { DocumentService, type NewDocument } from '@/services/document-service';
import { EmployeeService, type EmployeeListQuery } from '@/services/employee-service';
import type { UploadProgressListener } from '@/services/api/upload-progress';
import { downloadFile } from '@/shared/lib/download';
import { queryKeys } from '@/shared/lib/query-keys';
import { settleAll } from '@/shared/lib/settle-all';
import type { Document, Employee, EmployeeCreate, EmployeeUpdate, ListEmployees200 } from '@/types/api';

/** Writes of the employee module, with the cache updates each one needs. */

/** Parallel requests for bulk actions: fast, without flooding the API. */
const BULK_CONCURRENCY = 4;

function invalidateEmployee(queryClient: QueryClient, id?: string) {
  void queryClient.invalidateQueries({ queryKey: queryKeys.employees.lists() });
  void queryClient.invalidateQueries({ queryKey: queryKeys.employees.headcount() });
  void queryClient.invalidateQueries({ queryKey: queryKeys.audit.all });

  if (id) void queryClient.invalidateQueries({ queryKey: queryKeys.employees.account(id) });
}

function storeEmployee(queryClient: QueryClient, employee: Employee) {
  queryClient.setQueryData(queryKeys.employees.detail(employee.id), employee);
  invalidateEmployee(queryClient, employee.id);
}

export function useCreateEmployee() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (input: EmployeeCreate) => EmployeeService.create(input),
    onSuccess: (employee) => storeEmployee(queryClient, employee),
  });
}

export function useUpdateEmployee(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ version, changes }: { version: number; changes: EmployeeUpdate }) => EmployeeService.update(id, version, changes),
    onSuccess: (employee) => storeEmployee(queryClient, employee),
  });
}

/*
| Optimistic delete and restore: every cached directory page reflects the
| change at once and is rolled back if the API refuses. A list showing
| "all records" keeps the row and only flips its deleted state.
*/

type ListSnapshot = [QueryKey, ListEmployees200 | undefined][];

function listQueryOf(key: QueryKey): EmployeeListQuery | undefined {
  return key[2] as EmployeeListQuery | undefined;
}

function applyToLists(queryClient: QueryClient, ids: ReadonlySet<string>, deleted: boolean): ListSnapshot {
  const snapshot = queryClient.getQueriesData<ListEmployees200>({ queryKey: queryKeys.employees.lists() });
  const deletedAt = deleted ? new Date().toISOString() : null;

  for (const [key, page] of snapshot) {
    if (!page) continue;

    const keepsBoth = listQueryOf(key)?.recordState === 'all';
    const data = keepsBoth
      ? page.data.map((employee) => (ids.has(employee.id) ? { ...employee, deleted_at: deletedAt } : employee))
      : page.data.filter((employee) => !ids.has(employee.id));

    queryClient.setQueryData<ListEmployees200>(key, { ...page, data });
  }

  return snapshot;
}

function useOptimisticRecordState<Variables, Result>(
  mutationFn: (variables: Variables) => Promise<Result>,
  idsOf: (variables: Variables) => string[],
  deleted: boolean,
) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn,
    onMutate: async (variables) => {
      await queryClient.cancelQueries({ queryKey: queryKeys.employees.lists() });

      return { snapshot: applyToLists(queryClient, new Set(idsOf(variables)), deleted) };
    },
    onError: (_error, _variables, context) => {
      context?.snapshot.forEach(([key, data]) => queryClient.setQueryData(key, data));
    },
    onSettled: (_data, _error, variables) => {
      void queryClient.invalidateQueries({ queryKey: queryKeys.employees.all });
      void queryClient.invalidateQueries({ queryKey: queryKeys.audit.all });

      if (deleted) {
        idsOf(variables).forEach((id) => queryClient.removeQueries({ queryKey: queryKeys.employees.detail(id), exact: true }));
      }
    },
  });
}

export interface DeleteRequest {
  employee: Employee;
  reason: string;
}

export function useDeleteEmployee() {
  return useOptimisticRecordState(
    ({ employee, reason }: DeleteRequest) => EmployeeService.remove(employee.id, employee.meta.version, reason),
    ({ employee }) => [employee.id],
    true,
  );
}

export function useRestoreEmployee() {
  return useOptimisticRecordState((employee: Employee) => EmployeeService.restore(employee.id), (employee) => [employee.id], false);
}

/**
 * Bulk delete and restore send one request per employee (each with its own
 * version), so a refusal for one — e.g. a manager with direct reports — is
 * reported without blocking the rest. The mutation resolves with the
 * outcome; refused rows reappear when the lists are refetched.
 */
export function useBulkDeleteEmployees() {
  return useOptimisticRecordState(
    async ({ employees, reason }: { employees: Employee[]; reason: string }) =>
      settleAll(employees, (employee) => EmployeeService.remove(employee.id, employee.meta.version, reason), BULK_CONCURRENCY),
    ({ employees }) => employees.map((employee) => employee.id),
    true,
  );
}

export function useBulkRestoreEmployees() {
  return useOptimisticRecordState(
    async (employees: Employee[]) => settleAll(employees, (employee) => EmployeeService.restore(employee.id), BULK_CONCURRENCY),
    (employees) => employees.map((employee) => employee.id),
    false,
  );
}

export function useUploadEmployeePhoto(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ photo, onProgress }: { photo: Blob; onProgress?: UploadProgressListener }) => EmployeeService.uploadPhoto(id, photo, onProgress),
    onSuccess: (employee) => storeEmployee(queryClient, employee),
  });
}

export function useRemoveEmployeePhoto(employee: Employee) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => EmployeeService.removePhoto(employee.id),
    onSuccess: () => {
      queryClient.setQueryData<Employee>(queryKeys.employees.detail(employee.id), (current) =>
        current ? { ...current, photo_url: null, meta: { ...current.meta, version: current.meta.version + 1 } } : current,
      );
      invalidateEmployee(queryClient, employee.id);
    },
  });
}

export function useCreateEmployeeAccount(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (role: string) => EmployeeService.createAccount(id, role),
    onSuccess: (account) => {
      queryClient.setQueryData(queryKeys.employees.account(id), account);
      void queryClient.invalidateQueries({ queryKey: queryKeys.employees.detail(id) });
      invalidateEmployee(queryClient);
    },
  });
}

export function useChangeEmployeeRole(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (role: string) => EmployeeService.changeRole(id, role),
    onSuccess: (account) => {
      queryClient.setQueryData(queryKeys.employees.account(id), account);
      void queryClient.invalidateQueries({ queryKey: queryKeys.audit.all });
    },
  });
}

export function useUploadDocument(employeeId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ document, onProgress }: { document: NewDocument; onProgress?: UploadProgressListener }) => DocumentService.upload(employeeId, document, onProgress),
    onSuccess: (document) => {
      queryClient.setQueryData<Document[]>(queryKeys.employees.documents(employeeId), (current) => [document, ...(current ?? [])]);
      void queryClient.invalidateQueries({ queryKey: queryKeys.audit.all });
    },
  });
}

export function useDeleteDocument(employeeId: string) {
  const queryClient = useQueryClient();
  const key = queryKeys.employees.documents(employeeId);

  return useMutation({
    mutationFn: (document: Document) => DocumentService.remove(document.id),
    onMutate: async (document) => {
      await queryClient.cancelQueries({ queryKey: key });
      const previous = queryClient.getQueryData<Document[]>(key);
      queryClient.setQueryData<Document[]>(key, (current) => current?.filter((item) => item.id !== document.id));

      return { previous };
    },
    onError: (_error, _document, context) => queryClient.setQueryData(key, context?.previous),
    onSettled: () => {
      void queryClient.invalidateQueries({ queryKey: key });
      void queryClient.invalidateQueries({ queryKey: queryKeys.audit.all });
    },
  });
}

/** Downloads through the API (the bearer token is required) and saves the file. */
export function useDownloadDocument() {
  return useMutation({
    mutationFn: async (document: Document) => downloadFile(await DocumentService.download(document.id), document.file_name, document.mime_type),
  });
}
