<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Domain\ValueObjects;

use Meridian\Modules\Access\Contracts\Permission;

/**
 * Built-in role templates (docs/07 §4).
 *
 * Only tenant-wide roles are shipped for now. Scoped roles (line manager,
 * department head, HR partner) arrive with scope evaluation in the Employee
 * module, so a scoped role can never accidentally grant tenant-wide access.
 */
enum SystemRole: string
{
    case TenantAdmin = 'tenant_admin';
    case HrAdmin = 'hr_admin';
    case PayrollOfficer = 'payroll_officer';
    case Auditor = 'auditor';

    public function displayName(): string
    {
        return match ($this) {
            self::TenantAdmin => 'Tenant Administrator',
            self::HrAdmin => 'HR Administrator',
            self::PayrollOfficer => 'Payroll Officer',
            self::Auditor => 'Auditor',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TenantAdmin => 'Manages users, access, organisation settings and integrations. Has no HR data access by default (separation of duties).',
            self::HrAdmin => 'Full HR operations across the organisation, excluding compensation.',
            self::PayrollOfficer => 'Views employees and manages compensation.',
            self::Auditor => 'Read-only access to HR data and the audit trail, without sensitive fields.',
        };
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::TenantAdmin => [
                Permission::AccessManage,
                Permission::UserManage,
                Permission::TenantManage,
                Permission::IntegrationManage,
                Permission::AuditView,
            ],
            self::HrAdmin => [
                Permission::EmployeeView,
                Permission::EmployeeCreate,
                Permission::EmployeeUpdate,
                Permission::EmployeeViewSensitive,
                Permission::EmployeeUpdateSensitive,
                Permission::EmployeeTransfer,
                Permission::EmployeeTerminate,
                Permission::EmployeeExport,
                Permission::EmployeeDelete,
                Permission::OrganisationView,
                Permission::OrganisationManage,
                Permission::LeaveView,
                Permission::LeaveRequestOnBehalf,
                Permission::LeaveApprove,
                Permission::LeaveAdjust,
                Permission::LeaveConfigure,
                Permission::AttendanceView,
                Permission::AttendanceManage,
                Permission::AttendanceConfigure,
                Permission::DocumentView,
                Permission::DocumentUpload,
                Permission::DocumentDelete,
                Permission::DocumentViewConfidential,
            ],
            self::PayrollOfficer => [
                Permission::EmployeeView,
                Permission::CompensationView,
                Permission::CompensationManage,
            ],
            self::Auditor => [
                Permission::EmployeeView,
                Permission::OrganisationView,
                Permission::LeaveView,
                Permission::AttendanceView,
                Permission::DocumentView,
                Permission::AuditView,
            ],
        };
    }
}
