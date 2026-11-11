import { Toaster as Sonner } from 'sonner';
import { useTheme } from '@/shared/theme/theme-context';

/** Toast notifications; announced through an ARIA live region. */
export function Toaster() {
  const { resolved } = useTheme();

  return <Sonner theme={resolved} position="top-right" richColors closeButton />;
}
