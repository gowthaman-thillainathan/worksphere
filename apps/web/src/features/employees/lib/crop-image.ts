export interface CropArea {
  x: number;
  y: number;
  size: number;
}

/** Pan position of the crop square, -1 (left/top) to 1 (right/bottom); 0 is centred. */
export interface CropPosition {
  zoom: number;
  panX: number;
  panY: number;
}

export const MIN_ZOOM = 1;
export const MAX_ZOOM = 3;

const clamp = (value: number, min: number, max: number) => Math.min(Math.max(value, min), max);

/**
 * The square of the source image to keep. At zoom 1 it is the largest
 * centred square; zooming shrinks it and panning moves it within the image.
 */
export function cropArea(width: number, height: number, { zoom, panX, panY }: CropPosition): CropArea {
  const size = Math.min(width, height) / clamp(zoom, MIN_ZOOM, MAX_ZOOM);
  const centre = (length: number, pan: number) => (length - size) / 2 + (clamp(pan, -1, 1) * (length - size)) / 2;

  return { x: centre(width, panX), y: centre(height, panY), size };
}

/** Draws the area into a square canvas of `outputSize` pixels and encodes it. */
export function cropToBlob(image: CanvasImageSource, area: CropArea, outputSize: number, type: string): Promise<Blob> {
  const canvas = document.createElement('canvas');
  const size = Math.min(outputSize, Math.round(area.size));
  canvas.width = size;
  canvas.height = size;

  const context = canvas.getContext('2d');

  if (!context) return Promise.reject(new Error('The browser cannot crop images.'));

  context.drawImage(image, area.x, area.y, area.size, area.size, 0, 0, size, size);

  return new Promise((resolve, reject) => canvas.toBlob((blob) => (blob ? resolve(blob) : reject(new Error('The image could not be encoded.'))), type));
}
