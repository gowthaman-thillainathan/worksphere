import { memo } from 'react';
import { Avatar } from '@/components/ui/avatar';
import { useObjectUrl } from '@/hooks/use-object-url';
import type { Employee } from '@/types/api';
import { useEmployeePhoto } from '../api/employee-queries';

interface EmployeeAvatarProps {
  employee: Pick<Employee, 'id' | 'display_name' | 'photo_url' | 'meta'>;
  size?: 'sm' | 'md' | 'lg';
}

/** Initials immediately, the photo once its authenticated download finishes. Memoised for long tables. */
export const EmployeeAvatar = memo(function EmployeeAvatar({ employee, size = 'sm' }: EmployeeAvatarProps) {
  const { data } = useEmployeePhoto(employee);
  const src = useObjectUrl(data);

  return <Avatar name={employee.display_name} src={src} size={size} />;
});
