<?php

declare(strict_types=1);

namespace Meridian\Modules\Access\Contracts;

/**
 * The permission catalogue (docs/07 §3).
 *
 * Code is the source of truth: `php artisan access:sync-permissions` mirrors
 * these cases into the permissions table on every deploy.
 */
enum Permission: string
{
    case EmployeeView = 'employee.view';
    case EmployeeCreate = 'employee.create';
    case EmployeeUpdate = 'employee.update';
    case EmployeeViewSensitive = 'employee.view.sensitive';
    case EmployeeUpdateSensitive = 'employee.update.sensitive';
    case EmployeeTransfer = 'employee.transfer';
    case EmployeeTerminate = 'employee.terminate';
    case EmployeeExport = 'employee.export';
    case EmployeeDelete = 'employee.delete';

    case OrganisationView = 'organisation.view';
    case OrganisationManage = 'organisation.manage';

    case LeaveView = 'leave.view';
    case LeaveRequestOnBehalf = 'leave.request.on_behalf';
    case LeaveApprove = 'leave.approve';
    case LeaveAdjust = 'leave.adjust';
    case LeaveConfigure = 'leave.configure';

    case AttendanceView = 'attendance.view';
    case AttendanceManage = 'attendance.manage';
    case AttendanceConfigure = 'attendance.configure';

    case CompensationView = 'compensation.view';
    case CompensationManage = 'compensation.manage';

    case DocumentView = 'document.view';
    case DocumentUpload = 'document.upload';
    case DocumentDelete = 'document.delete';
    case DocumentViewConfidential = 'document.view.confidential';

    case AccessManage = 'access.manage';
    case UserManage = 'user.manage';

    case AuditView = 'audit.view';

    case TenantManage = 'tenant.manage';
    case IntegrationManage = 'integration.manage';

    private const MODULE_SEPARATOR = '.';

    /**
     * The bounded context that owns the permission, e.g. "employee".
     */
    public function module(): string
    {
        return explode(self::MODULE_SEPARATOR, $this->value, 2)[0];
    }

    /**
     * Sensitive permissions expose personal or security-critical data. Granting
     * them notifies other administrators; using them requires recent MFA.
     */
    public function isSensitive(): bool
    {
        return match ($this) {
            self::EmployeeViewSensitive,
            self::EmployeeUpdateSensitive,
            self::EmployeeExport,
            self::CompensationView,
            self::CompensationManage,
            self::DocumentViewConfidential,
            self::AccessManage,
            self::UserManage,
            self::TenantManage,
            self::IntegrationManage => true,
            default => false,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::EmployeeView => 'View employee profiles.',
            self::EmployeeCreate => 'Create and re-hire employees.',
            self::EmployeeUpdate => 'Update employee profiles.',
            self::EmployeeViewSensitive => 'View personal data such as national ID and date of birth.',
            self::EmployeeUpdateSensitive => 'Update personal data such as national ID and date of birth.',
            self::EmployeeTransfer => 'Transfer employees between positions and departments.',
            self::EmployeeTerminate => 'Terminate employment.',
            self::EmployeeExport => 'Export employee data.',
            self::EmployeeDelete => 'Delete and restore employee records.',
            self::OrganisationView => 'View departments, positions, grades and locations.',
            self::OrganisationManage => 'Manage departments, positions, grades and locations.',
            self::LeaveView => 'View leave requests, balances and calendars.',
            self::LeaveRequestOnBehalf => 'Submit leave requests on behalf of employees.',
            self::LeaveApprove => 'Approve or reject leave requests.',
            self::LeaveAdjust => 'Manually adjust leave balances.',
            self::LeaveConfigure => 'Configure leave types, policies and public holidays.',
            self::AttendanceView => 'View attendance records.',
            self::AttendanceManage => 'Correct attendance records.',
            self::AttendanceConfigure => 'Configure shifts.',
            self::CompensationView => 'View salaries and allowances.',
            self::CompensationManage => 'Change salaries and allowances.',
            self::DocumentView => 'View employee documents.',
            self::DocumentUpload => 'Upload employee documents.',
            self::DocumentDelete => 'Delete employee documents.',
            self::DocumentViewConfidential => 'View confidential employee documents.',
            self::AccessManage => 'Manage roles, permissions and role assignments.',
            self::UserManage => 'Invite, disable and unlock user accounts.',
            self::AuditView => 'View the audit trail.',
            self::TenantManage => 'Manage organisation settings and SSO.',
            self::IntegrationManage => 'Manage API tokens and webhooks.',
        };
    }

    /**
     * @param  list<string>  $values
     * @return list<self>
     */
    public static function fromValues(array $values): array
    {
        return array_values(array_filter(array_map(self::tryFrom(...), $values)));
    }
}
