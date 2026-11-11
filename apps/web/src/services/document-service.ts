import { deleteDocument, downloadDocument, listEmployeeDocuments, uploadEmployeeDocument } from './api/generated/endpoints/documents/documents';
import type { Document, UploadEmployeeDocumentBody } from './api/generated/model';
import { withUploadProgress, type UploadProgressListener } from './api/upload-progress';

export type NewDocument = UploadEmployeeDocumentBody;

export const DocumentService = {
  async list(employeeId: string, signal?: AbortSignal): Promise<Document[]> {
    return (await listEmployeeDocuments(employeeId, { signal })).data;
  },

  async upload(employeeId: string, document: NewDocument, onProgress?: UploadProgressListener): Promise<Document> {
    return (await uploadEmployeeDocument(employeeId, document, withUploadProgress(onProgress))).data;
  },

  download(documentId: string): Promise<Blob> {
    return downloadDocument(documentId);
  },

  remove(documentId: string): Promise<void> {
    return deleteDocument(documentId);
  },
};
