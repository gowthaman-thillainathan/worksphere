import { describe, expect, it } from 'vitest';
import { cropArea } from './crop-image';
import { checkPhotoDimensions, checkPhotoFile, PHOTO_RULES } from './photo-rules';

describe('photo rules', () => {
  it('accepts the image types and sizes the API accepts', () => {
    expect(checkPhotoFile({ type: 'image/png', size: 1024 })).toBeNull();
    expect(checkPhotoFile({ type: 'image/gif', size: 1024 })).toBe('type');
    expect(checkPhotoFile({ type: 'image/jpeg', size: PHOTO_RULES.maxBytes + 1 })).toBe('size');
  });

  it('checks the pixel dimensions', () => {
    expect(checkPhotoDimensions(400, 300)).toBeNull();
    expect(checkPhotoDimensions(400, 100)).toBe('too-small');
    expect(checkPhotoDimensions(5000, 400)).toBe('too-large');
  });
});

describe('cropArea', () => {
  it('takes the largest centred square at zoom 1', () => {
    expect(cropArea(800, 400, { zoom: 1, panX: 0, panY: 0 })).toEqual({ x: 200, y: 0, size: 400 });
  });

  it('zooms in and pans within the image bounds', () => {
    expect(cropArea(800, 400, { zoom: 2, panX: 1, panY: -1 })).toEqual({ x: 600, y: 0, size: 200 });
    expect(cropArea(800, 400, { zoom: 9, panX: 5, panY: 0 })).toMatchObject({ size: 400 / 3 });
  });
});
