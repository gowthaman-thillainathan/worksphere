<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Application\Actions;

use Meridian\Modules\Audit\Domain\Repositories\AuditEventRepository;
use Meridian\Modules\Audit\Domain\Services\AuditChainHasher;
use Meridian\Modules\Audit\Domain\ValueObjects\ChainVerification;

/**
 * Recomputes every hash in order. Any edited, deleted or re-ordered event
 * shows up as the first event whose stored hash no longer matches.
 */
final readonly class VerifyAuditChain
{
    public function __construct(private AuditEventRepository $events) {}

    public function handle(): ChainVerification
    {
        $previousHash = null;
        $checked = 0;

        foreach ($this->events->inChainOrder() as $event) {
            $checked++;
            $expected = AuditChainHasher::hash($previousHash, $event->toData());

            if ($event->prev_hash !== $previousHash || ! hash_equals($expected, $event->hash)) {
                return new ChainVerification($checked, $event->id);
            }

            $previousHash = $event->hash;
        }

        return new ChainVerification($checked, null);
    }
}
