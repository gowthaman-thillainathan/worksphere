import { Badge } from '@/components/ui/badge';
import type { EmployeeStatus } from '@/types/api';
import { STATUS_LABELS, STATUS_VARIANTS } from '../lib/labels';

interface EmployeeStatusBadgeProps {
  status: EmployeeStatus;
  /** Soft-deleted records are marked regardless of their employment status. */
  deleted?: boolean;
}

export function EmployeeStatusBadge({ status, deleted = false }: EmployeeStatusBadgeProps) {
  if (deleted) return <Badge variant="outline">Deleted</Badge>;

  return <Badge variant={STATUS_VARIANTS[status]}>{STATUS_LABELS[status]}</Badge>;
}
