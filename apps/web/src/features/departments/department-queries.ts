import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { DepartmentService } from '@/services/department-service';
import { queryKeys } from '@/shared/lib/query-keys';
import type { Department, DepartmentCreate, DepartmentUpdate } from '@/types/api';

/** Departments are reference data: cached longer and shared by every screen. */
export function useDepartments(enabled = true) {
  return useQuery({
    queryKey: queryKeys.departments.list(),
    queryFn: ({ signal }) => DepartmentService.list(signal),
    staleTime: 5 * 60_000,
    enabled,
  });
}

function useInvalidateDepartments() {
  const queryClient = useQueryClient();

  return () => {
    void queryClient.invalidateQueries({ queryKey: queryKeys.departments.all });
    void queryClient.invalidateQueries({ queryKey: queryKeys.audit.all });
  };
}

export function useSaveDepartment() {
  const invalidate = useInvalidateDepartments();

  return useMutation({
    mutationFn: ({ department, values }: { department?: Department; values: DepartmentCreate & DepartmentUpdate }) =>
      department ? DepartmentService.update(department.id, department.meta.version, values) : DepartmentService.create(values),
    onSuccess: invalidate,
  });
}

export function useDeleteDepartment() {
  const invalidate = useInvalidateDepartments();

  return useMutation({
    mutationFn: (department: Department) => DepartmentService.remove(department.id, department.meta.version),
    onSuccess: invalidate,
  });
}
