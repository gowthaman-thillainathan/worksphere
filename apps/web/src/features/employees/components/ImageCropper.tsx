import { useId, useRef, type KeyboardEvent, type PointerEvent } from 'react';
import { Label } from '@/components/ui/label';
import { cropArea, MAX_ZOOM, MIN_ZOOM, type CropPosition } from '../lib/crop-image';

const VIEWPORT = 240;
const KEYBOARD_PAN_STEP = 0.1;
const ZOOM_STEP = 0.05;

const ARROW_PAN: Record<string, [number, number]> = {
  ArrowLeft: [-KEYBOARD_PAN_STEP, 0],
  ArrowRight: [KEYBOARD_PAN_STEP, 0],
  ArrowUp: [0, -KEYBOARD_PAN_STEP],
  ArrowDown: [0, KEYBOARD_PAN_STEP],
};

const clampPan = (value: number) => Math.min(Math.max(value, -1), 1);

interface ImageCropperProps {
  src: string;
  width: number;
  height: number;
  position: CropPosition;
  onChange: (position: CropPosition) => void;
}

/**
 * Square crop with a circular preview: drag (or use the arrow keys) to move
 * the image, the slider to zoom. The same maths produces the final image
 * (cropArea), so what you see is what is uploaded.
 */
export function ImageCropper({ src, width, height, position, onChange }: ImageCropperProps) {
  const zoomId = useId();
  const drag = useRef<{ x: number; y: number; start: CropPosition } | null>(null);
  const area = cropArea(width, height, position);
  const scale = VIEWPORT / area.size;
  const panRange = (length: number) => ((length - area.size) / 2) * scale;

  const panBy = (start: CropPosition, dx: number, dy: number) => {
    const rangeX = panRange(width);
    const rangeY = panRange(height);

    onChange({
      ...start,
      panX: rangeX > 0 ? clampPan(start.panX - dx / rangeX) : 0,
      panY: rangeY > 0 ? clampPan(start.panY - dy / rangeY) : 0,
    });
  };

  const onPointerDown = (event: PointerEvent) => {
    event.currentTarget.setPointerCapture(event.pointerId);
    drag.current = { x: event.clientX, y: event.clientY, start: position };
  };

  const onPointerMove = (event: PointerEvent) => {
    if (drag.current) panBy(drag.current.start, event.clientX - drag.current.x, event.clientY - drag.current.y);
  };

  const onKeyDown = (event: KeyboardEvent) => {
    const step = ARROW_PAN[event.key];

    if (!step) return;
    event.preventDefault();
    onChange({ ...position, panX: clampPan(position.panX + step[0]), panY: clampPan(position.panY + step[1]) });
  };

  return (
    <div className="grid justify-items-center gap-4">
      <button
        type="button"
        aria-label="Photo position. Drag or use the arrow keys to move the photo inside the circle."
        onPointerDown={onPointerDown}
        onPointerMove={onPointerMove}
        onPointerUp={() => (drag.current = null)}
        onKeyDown={onKeyDown}
        className="relative cursor-move touch-none overflow-hidden rounded-full border-2 border-primary bg-muted outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
        style={{ width: VIEWPORT, height: VIEWPORT }}
      >
        <img
          src={src}
          alt=""
          draggable={false}
          className="pointer-events-none absolute max-w-none select-none"
          style={{ width: width * scale, height: height * scale, left: -area.x * scale, top: -area.y * scale }}
        />
      </button>
      <div className="grid w-full max-w-xs gap-2">
        <Label htmlFor={zoomId}>Zoom</Label>
        <input
          id={zoomId}
          type="range"
          min={MIN_ZOOM}
          max={MAX_ZOOM}
          step={ZOOM_STEP}
          value={position.zoom}
          onChange={(event) => onChange({ ...position, zoom: Number(event.target.value) })}
          className="accent-primary"
        />
      </div>
    </div>
  );
}
