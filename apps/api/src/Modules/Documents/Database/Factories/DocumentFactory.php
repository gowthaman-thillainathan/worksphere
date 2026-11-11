<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Meridian\Modules\Documents\Domain\ValueObjects\DocumentCategory;
use Meridian\Modules\Documents\Infrastructure\Persistence\Models\Document;

/**
 * @extends Factory<Document>
 */
final class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Set with forEmployee(): employees belong to another module.
            'employee_id' => fake()->uuid(),
            'category' => DocumentCategory::Contract,
            'title' => fake()->sentence(3),
            'file_name' => 'contract.pdf',
            'path' => 'documents/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'checksum_sha256' => hash('sha256', fake()->uuid()),
        ];
    }

    public function forEmployee(string $employeeId): self
    {
        return $this->state(['employee_id' => $employeeId]);
    }

    public function category(DocumentCategory $category): self
    {
        return $this->state(['category' => $category]);
    }
}
