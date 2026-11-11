import { Badge } from '@/components/ui/badge';
import type { EmployeeStatus } from '@/types/api';
import { STATUS_LABELS, STATUS_VARIANTS } from '../lib/labels';

export function EmployeeStatusBadge({ status }: { status: EmployeeStatus }) {
  return <Badge variant={STATUS_VARIANTS[status]}>{STATUS_LABELS[status]}</Badge>;
}
