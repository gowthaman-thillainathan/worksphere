<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

beforeEach(function (): void {
    Storage::fake(config('employee.photo.disk'));
    $this->token = tokenForRole('hr_admin');
    $this->employee = Employee::factory()->create();
});

function uploadPhoto(UploadedFile $file): Illuminate\Testing\TestResponse
{
    return test()->withToken(test()->token)
        ->post(route('employees.photo.store', test()->employee->id), ['photo' => $file], ['Accept' => 'application/json']);
}

it('stores a photo privately and serves it to authorised users', function (): void {
    uploadPhoto(UploadedFile::fake()->image('me.jpg', 400, 400))
        ->assertOk()
        ->assertJsonPath('data.photo_url', route('employees.photo.show', $this->employee->id))
        ->assertHeader('ETag', '"v2"');

    $path = $this->employee->fresh()->photo_path;

    Storage::disk(config('employee.photo.disk'))->assertExists($path);
    expect($path)->toStartWith("employees/{$this->employee->id}/")->toEndWith('.jpg');

    $this->withToken($this->token)
        ->get(route('employees.photo.show', $this->employee->id))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');

    expect(AuditEvent::query()->where('action', 'employee.photo_updated')->exists())->toBeTrue();
});

it('replaces the previous photo file', function (): void {
    uploadPhoto(UploadedFile::fake()->image('one.png', 300, 300))->assertOk();
    $first = $this->employee->fresh()->photo_path;

    uploadPhoto(UploadedFile::fake()->image('two.png', 300, 300))->assertOk();

    Storage::disk(config('employee.photo.disk'))->assertMissing($first);
    expect($this->employee->fresh()->photo_path)->not->toBe($first);
});

it('removes the photo', function (): void {
    uploadPhoto(UploadedFile::fake()->image('me.jpg', 300, 300))->assertOk();
    $path = $this->employee->fresh()->photo_path;

    $this->withToken($this->token)->deleteJson(route('employees.photo.destroy', $this->employee->id))->assertNoContent();

    Storage::disk(config('employee.photo.disk'))->assertMissing($path);
    $this->withToken($this->token)->getJson(route('employees.photo.show', $this->employee->id))
        ->assertNotFound()
        ->assertJsonPath('code', 'employee_photo_not_found');
});

it('rejects files that are not acceptable images', function (UploadedFile $file): void {
    uploadPhoto($file)->assertUnprocessable()->assertJsonStructure(['errors' => ['photo']]);

    expect($this->employee->fresh()->photo_path)->toBeNull();
})->with([
    'pdf' => fn () => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
    'svg' => fn () => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
    'too small' => fn () => UploadedFile::fake()->image('tiny.jpg', 64, 64),
    'too large' => fn () => UploadedFile::fake()->image('huge.jpg', 400, 400)->size(4096),
]);

it('requires employee.update to upload', function (): void {
    $this->token = tokenForRole('auditor');

    uploadPhoto(UploadedFile::fake()->image('me.jpg', 300, 300))->assertForbidden();
});
