import { ImagePlus } from 'lucide-react';
import { useId, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Modal } from '@/components/ui/dialog';
import { useObjectUrl } from '@/hooks/use-object-url';
import { ApiError } from '@/services/api/api-error';
import { describeError } from '@/shared/lib/error-messages';
import type { Employee } from '@/types/api';
import { useUploadEmployeePhoto } from '../api/employee-queries';

/** Limits mirror the API (2 MB, JPEG/PNG/WebP); the API re-checks the actual content. */
const MAX_BYTES = 2 * 1024 * 1024;
const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

interface PhotoUploadDialogProps {
  employee: Employee;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function PhotoUploadDialog({ employee, open, onOpenChange }: PhotoUploadDialogProps) {
  const inputId = useId();
  const [file, setFile] = useState<File>();
  const [error, setError] = useState<string>();
  const preview = useObjectUrl(file);
  const upload = useUploadEmployeePhoto(employee.id);

  const close = (next: boolean) => {
    if (!next) {
      setFile(undefined);
      setError(undefined);
    }
    onOpenChange(next);
  };

  const choose = (selected: File | undefined) => {
    setError(undefined);
    setFile(undefined);

    if (!selected) return;
    if (!ACCEPTED_TYPES.includes(selected.type)) return setError('Choose a JPEG, PNG or WebP image.');
    if (selected.size > MAX_BYTES) return setError('The image must be 2 MB or smaller.');

    setFile(selected);
  };

  const submit = () => {
    if (!file) return;

    upload.mutate(file, {
      onSuccess: () => {
        toast.success('Photo updated.');
        close(false);
      },
      onError: (uploadError) => {
        const apiError = ApiError.from(uploadError);
        setError(apiError.fieldErrors.photo?.[0] ?? describeError(apiError).description);
      },
    });
  };

  return (
    <Modal
      open={open}
      onOpenChange={close}
      title="Upload photo"
      description={`A square image of at least 128 × 128 pixels works best for ${employee.display_name}.`}
      footer={
        <>
          <Button variant="outline" onClick={() => close(false)} disabled={upload.isPending}>
            Cancel
          </Button>
          <Button onClick={submit} disabled={!file} loading={upload.isPending}>
            Upload
          </Button>
        </>
      }
    >
      <div className="grid gap-4">
        <label
          htmlFor={inputId}
          className="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-6 text-center text-sm text-muted-foreground hover:bg-muted/50 focus-within:outline-2 focus-within:outline-ring"
        >
          {preview ? (
            <img src={preview} alt={file?.name ?? ''} className="size-32 rounded-full object-cover" />
          ) : (
            <ImagePlus className="size-8" aria-hidden="true" />
          )}
          <span>{file ? file.name : 'Choose an image (JPEG, PNG or WebP, up to 2 MB)'}</span>
          <input
            id={inputId}
            type="file"
            accept={ACCEPTED_TYPES.join(',')}
            className="sr-only"
            aria-describedby={error ? `${inputId}-error` : undefined}
            onChange={(event) => choose(event.target.files?.[0])}
          />
        </label>
        {error && (
          <p id={`${inputId}-error`} role="alert" className="text-sm font-medium text-destructive">
            {error}
          </p>
        )}
      </div>
    </Modal>
  );
}
