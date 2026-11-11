/** Saves the content as a file through a temporary object URL. */
export function downloadFile(content: BlobPart, fileName: string, type: string): void {
  const url = URL.createObjectURL(content instanceof Blob ? content : new Blob([content], { type }));
  const link = document.createElement('a');
  link.href = url;
  link.download = fileName;
  link.click();
  URL.revokeObjectURL(url);
}
