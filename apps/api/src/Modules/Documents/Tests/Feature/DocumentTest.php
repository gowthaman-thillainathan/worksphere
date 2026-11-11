<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Meridian\Modules\Audit\Infrastructure\Persistence\Models\AuditEvent;
use Meridian\Modules\Documents\Domain\ValueObjects\DocumentCategory;
use Meridian\Modules\Documents\Infrastructure\Persistence\Models\Document;
use Meridian\Modules\Employee\Infrastructure\Persistence\Models\Employee;

beforeEach(function (): void {
    Storage::fake(config('documents.disk'));
    $this->token = tokenForRole('hr_admin');
    $this->employee = Employee::factory()->create();
});

function uploadDocument(array $fields): Illuminate\Testing\TestResponse
{
    return test()->withToken(test()->token)->post(
        route('employees.documents.store', test()->employee->id),
        [...['title' => 'Employment contract', 'category' => 'contract', 'file' => UploadedFile::fake()->create('contract.pdf', 120, 'application/pdf')], ...$fields],
        ['Accept' => 'application/json'],
    );
}

it('uploads a document, lists it and records it on the employee', function (): void {
    $id = uploadDocument([])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Employment contract')
        ->assertJsonPath('data.file_name', 'contract.pdf')
        ->assertJsonPath('data.confidential', false)
        ->json('data.id');

    $document = Document::query()->findOrFail($id);
    Storage::disk(config('documents.disk'))->assertExists($document->path);
    expect($document->path)->toStartWith("documents/{$this->employee->id}/")
        ->and($document->checksum_sha256)->toHaveLength(64);

    $this->withToken($this->token)->getJson(route('employees.documents.index', $this->employee->id))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.download_url', route('documents.download', $id));

    $event = AuditEvent::query()->where('action', 'document.uploaded')->sole();
    expect($event->subject_id)->toBe($this->employee->id)
        ->and($event->getAttribute('changes'))->toBe(['document' => [null, 'Employment contract']]);
});

it('downloads the file as an attachment', function (): void {
    $id = uploadDocument([])->json('data.id');

    $this->withToken($this->token)->get(route('documents.download', $id))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('contract.pdf');
});

it('validates the upload', function (array $fields, string $field): void {
    uploadDocument($fields)->assertUnprocessable()->assertJsonStructure(['errors' => [$field]]);
})->with([
    'no title' => [['title' => ''], 'title'],
    'unknown category' => [['category' => 'payslip'], 'category'],
    'executable' => [['file' => UploadedFile::fake()->create('run.exe', 10, 'application/x-msdownload')], 'file'],
    'too large' => [['file' => UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf')], 'file'],
    'expiry in the past' => [['expires_on' => '2020-01-01'], 'expires_on'],
]);

it('hides confidential documents from users without document.view.confidential', function (): void {
    $idProof = Document::factory()->forEmployee($this->employee->id)->category(DocumentCategory::IdProof)->create();
    Document::factory()->forEmployee($this->employee->id)->create();
    $auditor = tokenForRole('auditor');

    $this->withToken($auditor)->getJson(route('employees.documents.index', $this->employee->id))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.category', 'contract');

    $this->withToken($auditor)->get(route('documents.download', $idProof->id), ['Accept' => 'application/json'])->assertNotFound();
    $this->withToken($this->token)->getJson(route('employees.documents.index', $this->employee->id))->assertJsonCount(2, 'data');
});

it('soft deletes a document and records it', function (): void {
    $id = uploadDocument([])->json('data.id');

    $this->withToken($this->token)->deleteJson(route('documents.destroy', $id))->assertNoContent();

    $this->assertSoftDeleted('documents', ['id' => $id]);
    $this->withToken($this->token)->getJson(route('employees.documents.index', $this->employee->id))->assertJsonCount(0, 'data');
    expect(AuditEvent::query()->where('action', 'document.deleted')->exists())->toBeTrue();
});

it('enforces document permissions', function (): void {
    $payroll = tokenForRole('payroll_officer');
    $document = Document::factory()->forEmployee($this->employee->id)->create();

    $this->withToken($payroll)->getJson(route('employees.documents.index', $this->employee->id))->assertForbidden();
    $this->withToken(tokenForRole('auditor'))->deleteJson(route('documents.destroy', $document->id))->assertForbidden();
});

it('does not attach documents to deleted employees', function (): void {
    $this->employee->delete();

    uploadDocument([])->assertNotFound()->assertJsonPath('code', 'document_employee_not_found');
});
