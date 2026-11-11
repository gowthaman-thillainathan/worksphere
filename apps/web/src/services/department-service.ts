import {
  createDepartment,
  deleteDepartment,
  getDepartment,
  listDepartments,
  updateDepartment,
} from './api/generated/endpoints/departments/departments';
import type { Department, DepartmentCreate, DepartmentUpdate } from './api/generated/model';
import { ifMatch } from './api/concurrency';

export const DepartmentService = {
  async list(signal?: AbortSignal): Promise<{ departments: Department[]; total: number }> {
    const response = await listDepartments({ signal });

    return { departments: response.data, total: response.meta.total };
  },

  async get(id: string, signal?: AbortSignal): Promise<Department> {
    return (await getDepartment(id, { signal })).data;
  },

  async create(input: DepartmentCreate): Promise<Department> {
    return (await createDepartment(input)).data;
  },

  async update(id: string, version: number, changes: DepartmentUpdate): Promise<Department> {
    return (await updateDepartment(id, changes, ifMatch(version))).data;
  },

  remove(id: string, version: number): Promise<void> {
    return deleteDepartment(id, ifMatch(version));
  },
};
