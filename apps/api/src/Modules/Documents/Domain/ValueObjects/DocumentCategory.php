<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents\Domain\ValueObjects;

enum DocumentCategory: string
{
    case Contract = 'contract';
    case IdProof = 'id_proof';
    case Certificate = 'certificate';
    case Other = 'other';

    /**
     * Confidential documents are only visible with document.view.confidential.
     */
    public function isConfidential(): bool
    {
        return $this === self::IdProof;
    }

    /**
     * @return list<self>
     */
    public static function visible(bool $includeConfidential): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $category): bool => $includeConfidential || ! $category->isConfidential(),
        ));
    }
}
