import { Upload } from 'lucide-react';
import { useId, useState, type DragEvent, type ReactNode } from 'react';
import { cn } from '@/shared/lib/utils';

interface FileDropzoneProps {
  /** Comma-separated MIME types or extensions for the file picker. */
  accept: string;
  onFile: (file: File) => void;
  /** What to drop, e.g. "JPEG, PNG or WebP, up to 2 MB". */
  hint: string;
  error?: string;
  disabled?: boolean;
  children?: ReactNode;
}

/**
 * Drag and drop or click/keyboard to choose a file. The native input stays
 * in the tab order (visually hidden), so it works without a mouse.
 */
export function FileDropzone({ accept, onFile, hint, error, disabled = false, children }: FileDropzoneProps) {
  const inputId = useId();
  const errorId = `${inputId}-error`;
  const [dragging, setDragging] = useState(false);

  const onDrop = (event: DragEvent) => {
    event.preventDefault();
    setDragging(false);
    const file = event.dataTransfer.files[0];

    if (file && !disabled) onFile(file);
  };

  return (
    <div className="grid gap-2">
      <label
        htmlFor={inputId}
        onDragOver={(event) => {
          event.preventDefault();
          setDragging(true);
        }}
        onDragLeave={() => setDragging(false)}
        onDrop={onDrop}
        className={cn(
          'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-6 text-center text-sm text-muted-foreground transition-colors focus-within:outline-2 focus-within:outline-ring hover:bg-muted/50',
          dragging && 'border-primary bg-primary/5',
          error && 'border-destructive',
          disabled && 'pointer-events-none opacity-60',
        )}
      >
        {children ?? <Upload className="size-8" aria-hidden="true" />}
        <span>
          <span className="font-medium text-foreground">Drop a file here</span> or click to choose
        </span>
        <span className="text-xs">{hint}</span>
        <input
          id={inputId}
          type="file"
          accept={accept}
          className="sr-only"
          disabled={disabled}
          aria-describedby={error ? errorId : undefined}
          onChange={(event) => {
            const file = event.target.files?.[0];
            event.target.value = ''; // choosing the same file again still fires
            if (file) onFile(file);
          }}
        />
      </label>
      {error && (
        <p id={errorId} role="alert" className="text-sm font-medium text-destructive">
          {error}
        </p>
      )}
    </div>
  );
}
