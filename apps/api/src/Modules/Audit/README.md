# Audit Module

Tamper-evident business audit trail. Design: [docs/09 §6](../../../../../docs/09-logging-observability.md).

| Layer | Contents |
|---|---|
| `Contracts` (public) | `AuditRecorder`, `AuditRecord`, `ChangeSet` (field diff with masking) |
| `Domain` | `AuditChainHasher` (SHA-256 of previous hash + canonical JSON), `AuditContext` port, value objects |
| `Application` | `RecordAuditEvent` (appends under a chain-head row lock), `VerifyAuditChain`, `SearchAuditEvents` |
| `Infrastructure` | Append-only `AuditEvent` model, chain head, repository, request-based actor context, `audit:verify` |
| `Http` | `GET /audit-events` (requires `audit.view`) |

Usage from another module, inside the transaction that makes the change:

```php
$this->audit->record(new AuditRecord(
    action: 'employee.updated',
    subjectType: 'employee',
    subjectId: $employee->id,
    changes: ChangeSet::between($before, $after, Employee::SENSITIVE_FIELDS),
));
```
