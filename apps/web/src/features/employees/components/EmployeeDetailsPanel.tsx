import { Briefcase, Building2, Mail, UserRound } from 'lucide-react';
import type { ReactNode } from 'react';
import { Link } from 'react-router';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { paths } from '@/routes/paths';
import { formatDate } from '@/shared/lib/format';
import type { Employee } from '@/types/api';
import { EMPLOYMENT_TYPE_LABELS } from '../lib/labels';
import { EmployeeStatusBadge } from './EmployeeStatusBadge';

const NOT_SET = '—';

function Section({ title, icon, children }: { title: string; icon: ReactNode; children: ReactNode }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-base">
          {icon}
          {title}
        </CardTitle>
      </CardHeader>
      <CardContent>
        <dl className="grid gap-4 sm:grid-cols-2">{children}</dl>
      </CardContent>
    </Card>
  );
}

function Detail({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="min-w-0 space-y-1">
      <dt className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</dt>
      <dd className="break-words text-sm">{children}</dd>
    </div>
  );
}

interface EmployeeDetailsPanelProps {
  employee: Employee;
  /** Personal contact data is only included in the API response with employee.view.sensitive. */
  showPersonalData: boolean;
}

/** Employment, department, manager and contact sections of the profile. */
export function EmployeeDetailsPanel({ employee, showPersonalData }: EmployeeDetailsPanelProps) {
  return (
    <div className="grid gap-6 xl:grid-cols-2">
      <Section title="Employment" icon={<Briefcase className="size-4" aria-hidden="true" />}>
        <Detail label="Employee ID">
          <span className="font-mono">{employee.employee_number}</span>
        </Detail>
        <Detail label="Designation">{employee.job_title}</Detail>
        <Detail label="Employment type">{EMPLOYMENT_TYPE_LABELS[employee.employment_type]}</Detail>
        <Detail label="Status">
          <EmployeeStatusBadge status={employee.status} />
        </Detail>
        <Detail label="Join date">{formatDate(employee.hire_date)}</Detail>
        <Detail label="Termination date">{formatDate(employee.termination_date)}</Detail>
      </Section>

      <Section title="Department and manager" icon={<Building2 className="size-4" aria-hidden="true" />}>
        <Detail label="Department">{employee.department_name ?? NOT_SET}</Detail>
        <Detail label="Manager">
          {employee.manager ? (
            <Link to={paths.employee(employee.manager.id)} className="inline-flex items-center gap-1 text-primary hover:underline">
              <UserRound className="size-3.5" aria-hidden="true" />
              {employee.manager.display_name}
            </Link>
          ) : (
            NOT_SET
          )}
        </Detail>
      </Section>

      <Section title="Contact information" icon={<Mail className="size-4" aria-hidden="true" />}>
        <Detail label="Work e-mail">
          <a href={`mailto:${employee.work_email}`} className="text-primary hover:underline">
            {employee.work_email}
          </a>
        </Detail>
        {showPersonalData ? (
          <>
            <Detail label="Phone">{employee.phone ? <a href={`tel:${employee.phone}`}>{employee.phone}</a> : NOT_SET}</Detail>
            <Detail label="Personal e-mail">{employee.personal_email ?? NOT_SET}</Detail>
            <Detail label="Date of birth">{formatDate(employee.date_of_birth)}</Detail>
          </>
        ) : (
          <Detail label="Personal details">
            <span className="text-muted-foreground">Visible to people allowed to see personal data.</span>
          </Detail>
        )}
      </Section>
    </div>
  );
}
