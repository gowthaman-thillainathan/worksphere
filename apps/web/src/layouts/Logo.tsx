import { Link } from 'react-router';
import { paths } from '@/routes/paths';

export function Logo() {
  return (
    <Link to={paths.dashboard} className="flex items-center gap-2 px-1 text-lg font-semibold tracking-tight">
      <img src="/favicon.svg" alt="" className="size-7" />
      Meridian
    </Link>
  );
}
