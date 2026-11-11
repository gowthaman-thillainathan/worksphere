<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Meridian\Modules\Audit\Domain\ValueObjects\AuditEventCriteria;

final class ListAuditEventsRequest extends FormRequest
{
    private const MAX_PER_PAGE = 100;

    private const DEFAULT_PER_PAGE = 25;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array:subject_type,subject_id,actor_id,action'],
            'filter.subject_type' => ['sometimes', 'string', 'max:100'],
            'filter.subject_id' => ['sometimes', 'uuid'],
            'filter.actor_id' => ['sometimes', 'uuid'],
            'filter.action' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'between:1,'.self::MAX_PER_PAGE],
            'cursor' => ['sometimes', 'string', 'max:1000'],
        ];
    }

    public function toCriteria(): AuditEventCriteria
    {
        return new AuditEventCriteria(
            subjectType: $this->input('filter.subject_type'),
            subjectId: $this->input('filter.subject_id'),
            actorId: $this->input('filter.actor_id'),
            action: $this->input('filter.action'),
            perPage: $this->integer('per_page', self::DEFAULT_PER_PAGE),
            cursor: $this->input('cursor'),
        );
    }
}
