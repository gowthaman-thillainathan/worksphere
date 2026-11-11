import { useState } from 'react';
import { toast } from 'sonner';
import { FileDropzone } from '@/components/FileDropzone';
import { Button } from '@/components/ui/button';
import { Modal } from '@/components/ui/dialog';
import { Progress } from '@/components/ui/progress';
import { useObjectUrl } from '@/hooks/use-object-url';
import { ApiError } from '@/services/api/api-error';
import { describeError } from '@/shared/lib/error-messages';
import type { Employee } from '@/types/api';
import { useUploadEmployeePhoto } from '../api/employee-mutations';
import { cropArea, cropToBlob, type CropPosition } from '../lib/crop-image';
import { checkPhotoDimensions, checkPhotoFile, PHOTO_PROBLEM_MESSAGES, PHOTO_RULES } from '../lib/photo-rules';
import { ImageCropper } from './ImageCropper';

const INITIAL_POSITION: CropPosition = { zoom: 1, panX: 0, panY: 0 };

interface LoadedImage {
  file: File;
  element: HTMLImageElement;
}

function loadImage(url: string): Promise<HTMLImageElement> {
  return new Promise((resolve, reject) => {
    const image = new Image();
    image.onload = () => resolve(image);
    image.onerror = () => reject(new Error('The image could not be read.'));
    image.src = url;
  });
}

interface PhotoUploadDialogProps {
  employee: Employee;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/** Choose (or drop) a photo, crop it to a square, and upload it with progress. */
export function PhotoUploadDialog({ employee, open, onOpenChange }: PhotoUploadDialogProps) {
  const upload = useUploadEmployeePhoto(employee.id);
  const [file, setFile] = useState<File>();
  const [image, setImage] = useState<LoadedImage>();
  const [position, setPosition] = useState(INITIAL_POSITION);
  const [progress, setProgress] = useState(0);
  const [error, setError] = useState<string>();
  const preview = useObjectUrl(file);

  const close = (next: boolean) => {
    if (!next) {
      setFile(undefined);
      setImage(undefined);
      setPosition(INITIAL_POSITION);
      setProgress(0);
      setError(undefined);
    }
    onOpenChange(next);
  };

  const choose = async (selected: File) => {
    setError(undefined);
    setImage(undefined);
    const typeProblem = checkPhotoFile(selected);

    if (typeProblem) {
      setFile(undefined);
      setError(PHOTO_PROBLEM_MESSAGES[typeProblem]);

      return;
    }

    const url = URL.createObjectURL(selected);

    try {
      const element = await loadImage(url);
      const sizeProblem = checkPhotoDimensions(element.naturalWidth, element.naturalHeight);

      if (sizeProblem) {
        setError(PHOTO_PROBLEM_MESSAGES[sizeProblem]);

        return;
      }

      setFile(selected);
      setImage({ file: selected, element });
      setPosition(INITIAL_POSITION);
    } catch {
      setError('The file could not be read as an image.');
    } finally {
      URL.revokeObjectURL(url);
    }
  };

  const submit = async () => {
    if (!image) return;

    try {
      const area = cropArea(image.element.naturalWidth, image.element.naturalHeight, position);
      const cropped = await cropToBlob(image.element, area, PHOTO_RULES.outputSize, image.file.type);
      await upload.mutateAsync({ photo: new File([cropped], image.file.name, { type: image.file.type }), onProgress: setProgress });
      toast.success('Photo updated.');
      close(false);
    } catch (uploadError) {
      const apiError = ApiError.from(uploadError);
      setProgress(0);
      setError(apiError.fieldErrors.photo?.[0] ?? describeError(apiError).description);
    }
  };

  return (
    <Modal
      open={open}
      onOpenChange={close}
      title="Profile photo"
      description={`Choose a photo of ${employee.display_name}, then position it in the circle.`}
      footer={
        <>
          <Button variant="outline" onClick={() => close(false)} disabled={upload.isPending}>
            Cancel
          </Button>
          {image && (
            <Button variant="outline" onClick={() => setImage(undefined)} disabled={upload.isPending}>
              Choose another
            </Button>
          )}
          <Button onClick={() => void submit()} disabled={!image} loading={upload.isPending}>
            Save photo
          </Button>
        </>
      }
    >
      <div className="grid gap-4">
        {image && preview ? (
          <ImageCropper src={preview} width={image.element.naturalWidth} height={image.element.naturalHeight} position={position} onChange={setPosition} />
        ) : (
          <FileDropzone accept={PHOTO_RULES.types.join(',')} onFile={(selected) => void choose(selected)} hint="JPEG, PNG or WebP, at least 128 × 128 pixels, up to 2 MB" />
        )}
        {upload.isPending && <Progress value={progress} label="Upload progress" />}
        {error && (
          <p role="alert" className="text-sm font-medium text-destructive">
            {error}
          </p>
        )}
      </div>
    </Modal>
  );
}
