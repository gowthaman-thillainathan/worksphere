# Documents Module

Files attached to employees: contracts, ID proofs and certificates. Design: [docs/03 §7](../../../../../docs/03-database-design.md), [docs/05](../../../../../docs/05-api-structure.md).

| Layer | Contents |
|---|---|
| `Domain` | `DocumentCategory` (`id_proof` is confidential), `DocumentAuditAction`; ports `DocumentRepository`, `DocumentStorage`, `EmployeeLookup`; exceptions |
| `Application` | `UploadDocument` (stores the file, then the record with a SHA-256 checksum), `DeleteDocument` (soft delete, file kept); queries `ListEmployeeDocuments`, `GetDocumentFile` |
| `Infrastructure` | Eloquent model, repository, private filesystem storage (`config/documents.php`), adapter onto `Employee\Contracts\EmployeeDirectory` |
| `Http` | `DocumentController`, `UploadDocumentRequest` (type from content, ≤ 10 MB, future expiry date), `DocumentResource` |

| Permission | Allows |
|---|---|
| `document.view` | List and download documents (confidential ones are hidden and answer 404 without `document.view.confidential`) |
| `document.upload` | Attach a document |
| `document.delete` | Delete a document |

**Audit:** `document.uploaded` / `document.deleted` are recorded with the **employee** as subject (`changes.document` holds the title), so they appear in the employee's history.

**Depends on:** `Employee\Contracts`, `Audit\Contracts`, `Access\Contracts`. The Employee module does not depend on Documents (architecture test).

**Next:** presigned S3 uploads and downloads (`upload-url` / `download-url`) instead of streaming through the API, document versions, expiry reminders.
