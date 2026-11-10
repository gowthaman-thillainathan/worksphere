<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Access\Domain\Repositories\PermissionRepository;
use Meridian\Modules\Access\Infrastructure\Authorization\CachedRoleBasedAuthorizer;

/**
 * In-memory repository that counts how often grants are loaded.
 */
final class InMemoryPermissionRepository implements PermissionRepository
{
    public int $loads = 0;

    /** @param array<string, list<string>> $grants */
    public function __construct(public array $grants = []) {}

    public function syncCatalogue(array $catalogue): int
    {
        return 0;
    }

    public function all(): array
    {
        return [];
    }

    public function idsOf(array $permissions): array
    {
        return [];
    }

    public function keysGrantedTo(string $userId): array
    {
        $this->loads++;

        return $this->grants[$userId] ?? [];
    }
}

beforeEach(function (): void {
    $this->repository = new InMemoryPermissionRepository(['user-1' => ['employee.view', 'leave.approve']]);
    $this->cache = new Repository(new ArrayStore);
    $this->authorizer = fn (): CachedRoleBasedAuthorizer => new CachedRoleBasedAuthorizer($this->repository, $this->cache, 600);
});

it('allows only granted permissions', function (): void {
    $authorizer = ($this->authorizer)();

    expect($authorizer->allows('user-1', Permission::LeaveApprove))->toBeTrue()
        ->and($authorizer->allows('user-1', Permission::CompensationView))->toBeFalse()
        ->and($authorizer->allows('user-2', Permission::EmployeeView))->toBeFalse();
});

it('denies by default when the user has no roles', function (): void {
    expect(($this->authorizer)()->permissionsFor('nobody'))->toBe([]);
});

it('loads grants once and serves later requests from the cache', function (): void {
    ($this->authorizer)()->permissionsFor('user-1');
    ($this->authorizer)()->permissionsFor('user-1');

    expect($this->repository->loads)->toBe(1);
});

it('reloads grants after being told to forget a user', function (): void {
    $authorizer = ($this->authorizer)();
    $authorizer->permissionsFor('user-1');

    $this->repository->grants['user-1'][] = 'compensation.view';
    $authorizer->forget('user-1');

    expect($authorizer->allows('user-1', Permission::CompensationView))->toBeTrue()
        ->and($this->repository->loads)->toBe(2);
});
