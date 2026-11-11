<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation\Application\Data;

final readonly class DepartmentData
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description = null,
        public ?string $parentId = null,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function toAttributes(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'parent_id' => $this->parentId,
        ];
    }
}
