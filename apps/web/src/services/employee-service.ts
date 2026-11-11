import {
  createEmployee,
  deleteEmployee,
  deleteEmployeePhoto,
  getEmployee,
  getEmployeeHeadcount,
  getEmployeePhoto,
  listEmployees,
  restoreEmployee,
  updateEmployee,
  uploadEmployeePhoto,
} from './api/generated/endpoints/employees/employees';
import type {
  Employee,
  EmployeeCreate,
  EmployeeStatus,
  EmployeeUpdate,
  EmploymentType,
  Headcount,
  ListEmployees200,
  ListEmployeesParams,
} from './api/generated/model';
import { ifMatch } from './api/concurrency';

export type EmployeeSortField = 'employee_number' | 'first_name' | 'last_name' | 'hire_date' | 'created_at';

export interface EmployeeSort {
  field: EmployeeSortField;
  direction: 'asc' | 'desc';
}

/** The directory query in application terms; translated to API parameters here only. */
export interface EmployeeListQuery {
  search?: string;
  statuses?: EmployeeStatus[];
  employmentTypes?: EmploymentType[];
  departmentId?: string;
  managerId?: string;
  trashed?: 'with' | 'only';
  sort?: EmployeeSort[];
  perPage?: number;
  cursor?: string;
}

export function toListEmployeesParams(query: EmployeeListQuery): ListEmployeesParams {
  const params: ListEmployeesParams = {};

  if (query.search) params.q = query.search;
  if (query.statuses?.length) params['filter[status]'] = query.statuses.join(',');
  if (query.employmentTypes?.length) params['filter[employment_type]'] = query.employmentTypes.join(',');
  if (query.departmentId) params['filter[department_id]'] = query.departmentId;
  if (query.managerId) params['filter[manager_id]'] = query.managerId;
  if (query.trashed) params['filter[trashed]'] = query.trashed;
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

  remove(id: string, version: number): Promise<void> {
    return deleteEmployee(id, ifMatch(version));
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

  async uploadPhoto(id: string, photo: File): Promise<Employee> {
    return (await uploadEmployeePhoto(id, { photo })).data;
  },

  removePhoto(id: string): Promise<void> {
    return deleteEmployeePhoto(id);
  },
};
