import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { FormField } from '@/components/FormField';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useFormServerErrors } from '@/hooks/use-form-server-errors';
import type { Department } from '@/types/api';

const schema = z.object({
  code: z
    .string()
    .trim()
    .min(1, 'Enter a code.')
    .max(20)
    .regex(/^[A-Za-z0-9][A-Za-z0-9_-]*$/, 'Use letters, digits, "-" and "_".'),
  name: z.string().trim().min(1, 'Enter a name.').max(150),
  description: z.string().trim().max(500),
  parent_id: z.string(),
});

export type DepartmentFormValues = z.infer<typeof schema>;

const FIELDS = ['code', 'name', 'description', 'parent_id'] as const;
const CODE_FIELDS = { department_code_in_use: 'code', invalid_parent_department: 'parent_id' } as const;

interface DepartmentFormProps {
  id: string;
  department?: Department;
  departments: Department[];
  onSubmit: (values: DepartmentFormValues) => Promise<void>;
}

/** Rendered inside a Drawer; the drawer's submit button targets this form by id. */
export function DepartmentForm({ id, department, departments, onSubmit }: DepartmentFormProps) {
  const { register, handleSubmit, setError, formState } = useForm<DepartmentFormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      code: department?.code ?? '',
      name: department?.name ?? '',
      description: department?.description ?? '',
      parent_id: department?.parent?.id ?? '',
    },
  });
  const handleServerError = useFormServerErrors(setError, { fields: FIELDS, codeFields: CODE_FIELDS });
  const parents = departments.filter((candidate) => candidate.id !== department?.id);

  const submit = handleSubmit(async (values) => {
    try {
      await onSubmit(values);
    } catch (error) {
      handleServerError(error);
    }
  });

  return (
    <form id={id} onSubmit={submit} noValidate className="grid gap-4">
      <FormField label="Name" error={formState.errors.name?.message} required>
        <Input {...register('name')} />
      </FormField>
      <FormField label="Code" error={formState.errors.code?.message} hint="Short unique identifier, e.g. ENG." required>
        <Input className="uppercase" {...register('code')} />
      </FormField>
      <FormField label="Parent department" error={formState.errors.parent_id?.message}>
        <Select {...register('parent_id')}>
          <option value="">None (top level)</option>
          {parents.map((parent) => (
            <option key={parent.id} value={parent.id}>
              {parent.name}
            </option>
          ))}
        </Select>
      </FormField>
      <FormField label="Description" error={formState.errors.description?.message}>
        <Textarea rows={3} {...register('description')} />
      </FormField>
    </form>
  );
}
