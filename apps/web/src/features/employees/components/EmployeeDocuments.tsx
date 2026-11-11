import { zodResolver } from '@hookform/resolvers/zod';
import { Download, FileText, Lock, Trash2, Upload } from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { FileDropzone } from '@/components/FileDropzone';
import { FormField } from '@/components/FormField';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Modal } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { Select } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { useFormServerErrors } from '@/hooks/use-form-server-errors';
import { ApiError } from '@/services/api/api-error';
import { useAuth } from '@/shared/auth/use-auth';
import { describeError } from '@/shared/lib/error-messages';
import { formatFileSize } from '@/shared/lib/file-size';
import { formatDate } from '@/shared/lib/format';
import { DocumentCategory, type Document } from '@/types/api';
import { Permission } from '@/types/permissions';
import { useEmployeeDocuments } from '../api/employee-queries';
import { useDeleteDocument, useDownloadDocument, useUploadDocument } from '../api/employee-mutations';
import { checkDocumentFile, DOCUMENT_RULES, documentFormSchema, type DocumentFormValues } from '../lib/document-rules';
import { DOCUMENT_CATEGORIES, DOCUMENT_CATEGORY_LABELS } from '../lib/labels';

const FIELDS = ['title', 'category', 'expires_on'] as const;

/** Files attached to the employee: contracts, ID proofs (confidential), certificates. */
export function EmployeeDocuments({ employeeId }: { employeeId: string }) {
  const { can } = useAuth();
  const documents = useEmployeeDocuments(employeeId, true);
  const download = useDownloadDocument();
  const remove = useDeleteDocument(employeeId);
  const [uploading, setUploading] = useState(false);
  const [deleting, setDeleting] = useState<Document | null>(null);

  const onDownload = (document: Document) =>
    download.mutate(document, { onError: (error) => toast.error(describeError(error).title, { description: describeError(error).description }) });

  const confirmDelete = () => {
    if (!deleting) return;

    remove.mutate(deleting, {
      onSuccess: () => toast.success(`${deleting.title} was deleted.`),
      onError: (error) => toast.error(describeError(error).title, { description: describeError(error).description }),
    });
    setDeleting(null);
  };

  return (
    <div className="grid gap-4">
      {can(Permission.DocumentUpload) && (
        <div className="flex justify-end">
          <Button onClick={() => setUploading(true)}>
            <Upload aria-hidden="true" />
            Upload document
          </Button>
        </div>
      )}

      {documents.isPending && (
        <div className="space-y-2" aria-busy="true" aria-label="Loading documents">
          <Skeleton className="h-14" />
          <Skeleton className="h-14" />
        </div>
      )}
      {documents.isError && <ErrorState error={documents.error} onRetry={() => documents.refetch()} />}
      {documents.data?.length === 0 && <EmptyState icon={FileText} title="No documents yet" description="Contracts, ID proofs and certificates appear here." />}

      {documents.data && documents.data.length > 0 && (
        <ul className="divide-y rounded-xl border bg-card" aria-label="Documents">
          {documents.data.map((document) => (
            <li key={document.id} className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
              <FileText className="hidden size-8 shrink-0 text-muted-foreground sm:block" aria-hidden="true" />
              <div className="min-w-0 flex-1 space-y-1">
                <div className="flex flex-wrap items-center gap-2">
                  <span className="font-medium">{document.title}</span>
                  <Badge variant="secondary">{DOCUMENT_CATEGORY_LABELS[document.category]}</Badge>
                  {document.confidential && (
                    <Badge variant="warning">
                      <Lock className="size-3" aria-hidden="true" />
                      Confidential
                    </Badge>
                  )}
                </div>
                <p className="truncate text-xs text-muted-foreground">
                  {document.file_name} · {formatFileSize(document.size_bytes)} · Uploaded {formatDate(document.created_at)}
                  {document.expires_on && ` · Expires ${formatDate(document.expires_on)}`}
                </p>
              </div>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  aria-label={`Download ${document.title}`}
                  onClick={() => onDownload(document)}
                  loading={download.isPending && download.variables.id === document.id}
                >
                  <Download aria-hidden="true" />
                  Download
                </Button>
                {can(Permission.DocumentDelete) && (
                  <Button variant="ghost" size="icon" className="text-destructive" aria-label={`Delete ${document.title}`} onClick={() => setDeleting(document)}>
                    <Trash2 aria-hidden="true" />
                  </Button>
                )}
              </div>
            </li>
          ))}
        </ul>
      )}

      <UploadDocumentDialog employeeId={employeeId} open={uploading} onOpenChange={setUploading} />
      <ConfirmDialog
        open={deleting !== null}
        onOpenChange={(open) => !open && setDeleting(null)}
        title={`Delete ${deleting?.title ?? 'document'}?`}
        description="It will no longer be listed. The deletion is recorded in the employee's history."
        confirmLabel="Delete document"
        destructive
        onConfirm={confirmDelete}
      />
    </div>
  );
}

function UploadDocumentDialog({ employeeId, open, onOpenChange }: { employeeId: string; open: boolean; onOpenChange: (open: boolean) => void }) {
  const upload = useUploadDocument(employeeId);
  const [file, setFile] = useState<File>();
  const [fileError, setFileError] = useState<string>();
  const [progress, setProgress] = useState(0);
  const { register, handleSubmit, reset, setError, formState } = useForm<DocumentFormValues>({
    resolver: zodResolver(documentFormSchema),
    defaultValues: { title: '', category: DocumentCategory.contract, expires_on: '' },
  });
  const handleServerError = useFormServerErrors(setError, { fields: FIELDS });

  const close = (next: boolean) => {
    if (!next) {
      reset();
      setFile(undefined);
      setFileError(undefined);
      setProgress(0);
    }
    onOpenChange(next);
  };

  const choose = (selected: File) => {
    const problem = checkDocumentFile(selected);
    setFileError(problem ?? undefined);
    setFile(problem ? undefined : selected);
  };

  const submit = handleSubmit(async (values) => {
    if (!file) {
      setFileError('Choose a file to upload.');

      return;
    }

    try {
      await upload.mutateAsync({
        document: { file, title: values.title, category: values.category, expires_on: values.expires_on || null },
        onProgress: setProgress,
      });
      toast.success(`${values.title} was uploaded.`);
      close(false);
    } catch (error) {
      setProgress(0);
      const fileMessage = ApiError.from(error).fieldErrors.file?.[0];

      if (fileMessage) setFileError(fileMessage);
      else handleServerError(error);
    }
  });

  return (
    <Modal
      open={open}
      onOpenChange={close}
      title="Upload document"
      description="Stored privately; only people allowed to see documents can download it."
      footer={
        <>
          <Button variant="outline" onClick={() => close(false)} disabled={formState.isSubmitting}>
            Cancel
          </Button>
          <Button type="submit" form="upload-document-form" loading={formState.isSubmitting}>
            Upload
          </Button>
        </>
      }
    >
      <form id="upload-document-form" onSubmit={submit} noValidate className="grid gap-4">
        <FileDropzone accept={DOCUMENT_RULES.accept} onFile={choose} hint="PDF, Word or image, up to 10 MB" error={fileError} disabled={formState.isSubmitting}>
          {file ? <FileText className="size-8 text-primary" aria-hidden="true" /> : undefined}
        </FileDropzone>
        {file && (
          <p className="text-sm">
            <span className="font-medium">{file.name}</span> <span className="text-muted-foreground">({formatFileSize(file.size)})</span>
          </p>
        )}
        <FormField label="Title" required error={formState.errors.title?.message}>
          <Input maxLength={DOCUMENT_RULES.titleMaxLength} {...register('title')} />
        </FormField>
        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label="Category" required error={formState.errors.category?.message} hint="ID proofs are confidential.">
            <Select {...register('category')}>
              {DOCUMENT_CATEGORIES.map((category) => (
                <option key={category} value={category}>
                  {DOCUMENT_CATEGORY_LABELS[category]}
                </option>
              ))}
            </Select>
          </FormField>
          <FormField label="Expires on" error={formState.errors.expires_on?.message}>
            <Input type="date" {...register('expires_on')} />
          </FormField>
        </div>
        {formState.isSubmitting && <Progress value={progress} label="Upload progress" />}
      </form>
    </Modal>
  );
}
