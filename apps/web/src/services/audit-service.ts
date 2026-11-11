import { listAuditEvents } from './api/generated/endpoints/audit/audit';
import type { ListAuditEvents200 } from './api/generated/model';

export interface AuditQuery {
  subjectType?: string;
  subjectId?: string;
  perPage?: number;
  cursor?: string;
}

export const AuditService = {
  list(query: AuditQuery, signal?: AbortSignal): Promise<ListAuditEvents200> {
    return listAuditEvents(
      {
        ...(query.subjectType && { 'filter[subject_type]': query.subjectType }),
        ...(query.subjectId && { 'filter[subject_id]': query.subjectId }),
        ...(query.perPage && { per_page: query.perPage }),
        ...(query.cursor && { cursor: query.cursor }),
      },
      { signal },
    );
  },
};
