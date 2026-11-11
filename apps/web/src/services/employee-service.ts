import {
  changeEmployeeRole,
  createEmployee,
  createEmployeeAccount,
  deleteEmployee,
  deleteEmployeePhoto,
  getEmployee,
  getEmployeeAccount,
  getEmployeeHeadcount,
  getEmployeePhoto,
  listEmployees,
  restoreEmployee,
  updateEmployee,
  uploadEmployeePhoto,
} from './api/generated/endpoints/employees/employees';
import type {
  Employee,
  EmployeeAccount,
  EmployeeCreate,
  EmployeeStatus,
  EmployeeUpdate,
  EmploymentType,
  Headcount,
  ListEmployees200,
  ListEmployeesParams,
} from './api/generated/model';
import { ifMatch } from './api/concurrency';
import { withUploadProgress, type UploadProgressListener } from './api/upload-progress';

export type EmployeeSortField = 'employee_number' | 'first_name' | 'last_name' | 'hire_date' | 'created_at' | 'department';

export interface EmployeeSort {
  field: EmployeeSortField;
  direction: 'asc' | 'desc';
}

/** Active records, deleted records, or both. */
export type EmployeeRecordState = 'active' | 'deleted' | 'all';

/** The directory query in application terms; translated to API parameters here only. */
export interface EmployeeListQuery {
  search?: string;
  name?: string;
  employeeNumber?: string;
  email?: string;
  jobTitle?: string;
  statuses?: EmployeeStatus[];
  employmentTypes?: EmploymentType[];
  departmentId?: string;
  managerId?: string;
  recordState?: EmployeeRecordState;
  sort?: EmployeeSort[];
  perPage?: number;
  cursor?: string;
}

const TRASHED_FILTER = { active: undefined, deleted: 'only', all: 'with' } as const;

export function toListEmployeesParams(query: EmployeeListQuery): ListEmployeesParams {
  const params: ListEmployeesParams = {};
  const trashed = TRASHED_FILTER[query.recordState ?? 'active'];

  if (query.search) params.q = query.search;
  if (query.name) params['filter[name]'] = query.name;
  if (query.employeeNumber) params['filter[employee_number]'] = query.employeeNumber;
  if (query.email) params['filter[email]'] = query.email;
  if (query.jobTitle) params['filter[job_title]'] = query.jobTitle;
  if (query.statuses?.length) params['filter[status]'] = query.statuses.join(',');
  if (query.employmentTypes?.length) params['filter[employment_type]'] = query.employmentTypes.join(',');
  if (query.departmentId) params['filter[department_id]'] = query.departmentId;
  if (query.managerId) params['filter[manager_id]'] = query.managerId;
  if (trashed) params['filter[trashed]'] = trashed;
  if (query.sort?.length) params.sort = query.sort.map((s) => (s.direction === 'desc' ? `-${s.field}` : s.field)).join(',');
  if (query.perPage) params.per_page = query.perPage;
  if (query.cursor) params.cursor = query.cursor;

  return params;
}

export const EmployeeService = {
  list(query: EmployeeListQuery, signal?: AbortSignal): Promise<ListEmployees200> {
    return listEmployees(toListEmployeesParams(query), { signal });
  },

  async get(id: string, signal?: AbortSignal): Promise<Employee> {
    return (await getEmployee(id, { signal })).data;
  },

  async create(input: EmployeeCreate): Promise<Employee> {
    return (await createEmployee(input)).data;
  },

  async update(id: string, version: number, changes: EmployeeUpdate): Promise<Employee> {
    return (await updateEmployee(id, changes, ifMatch(version))).data;
  },

  /** Soft delete; the reason is kept with the record and in the audit trail. */
  remove(id: string, version: number, reason: string): Promise<void> {
    return deleteEmployee(id, { reason }, ifMatch(version));
  },

  async restore(id: string): Promise<Employee> {
    return (await restoreEmployee(id)).data;
  },

  async headcount(signal?: AbortSignal): Promise<Headcount> {
    return (await getEmployeeHeadcount({ signal })).data;
  },

  photo(id: string, signal?: AbortSignal): Promise<Blob> {
    return getEmployeePhoto(id, { signal });
  },

  async uploadPhoto(id: string, photo: Blob, onProgress?: UploadProgressListener): Promise<Employee> {
    return (await uploadEmployeePhoto(id, { photo }, withUploadProgress(onProgress))).data;
  },

  removePhoto(id: string): Promise<void> {
    return deleteEmployeePhoto(id);
  },

  async account(id: string, signal?: AbortSignal): Promise<EmployeeAccount> {
    return (await getEmployeeAccount(id, { signal })).data;
  },

  async createAccount(id: string, role: string): Promise<EmployeeAccount> {
    return (await createEmployeeAccount(id, { role })).data;
  },

  async changeRole(id: string, role: string): Promise<EmployeeAccount> {
    return (await changeEmployeeRole(id, { role })).data;
  },
};
