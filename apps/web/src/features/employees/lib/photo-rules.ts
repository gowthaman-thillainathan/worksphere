/**
 * Mirrors the API's photo rules (config/employee.php) for instant feedback.
 * The API re-checks the actual file content, so these are a convenience,
 * not a security boundary.
 */
export const PHOTO_RULES = {
  maxBytes: 2 * 1024 * 1024,
  minDimension: 128,
  maxDimension: 4096,
  types: ['image/jpeg', 'image/png', 'image/webp'],
  /** Size of the square image produced by the cropper. */
  outputSize: 512,
} as const;

export type PhotoProblem = 'type' | 'size' | 'too-small' | 'too-large';

export const PHOTO_PROBLEM_MESSAGES: Record<PhotoProblem, string> = {
  type: 'Choose a JPEG, PNG or WebP image.',
  size: 'The image must be 2 MB or smaller.',
  'too-small': `The image must be at least ${PHOTO_RULES.minDimension} × ${PHOTO_RULES.minDimension} pixels.`,
  'too-large': `The image must be at most ${PHOTO_RULES.maxDimension} × ${PHOTO_RULES.maxDimension} pixels.`,
};

export function checkPhotoFile(file: Pick<File, 'type' | 'size'>): PhotoProblem | null {
  if (!(PHOTO_RULES.types as readonly string[]).includes(file.type)) return 'type';
  if (file.size > PHOTO_RULES.maxBytes) return 'size';

  return null;
}

export function checkPhotoDimensions(width: number, height: number): PhotoProblem | null {
  if (Math.min(width, height) < PHOTO_RULES.minDimension) return 'too-small';
  if (Math.max(width, height) > PHOTO_RULES.maxDimension) return 'too-large';

  return null;
}
