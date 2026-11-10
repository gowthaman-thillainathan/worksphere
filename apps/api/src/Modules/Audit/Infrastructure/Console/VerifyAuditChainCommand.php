<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit\Infrastructure\Console;

use Illuminate\Console\Command;
use Meridian\Modules\Audit\Application\Actions\VerifyAuditChain;
use Psr\Log\LoggerInterface;

final class VerifyAuditChainCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'audit:verify';

    /**
     * @var string
     */
    protected $description = 'Verify the audit trail hash chain (scheduled nightly)';

    public function handle(VerifyAuditChain $verifyAuditChain, LoggerInterface $logger): int
    {
        $result = $verifyAuditChain->handle();

        if ($result->isIntact()) {
            $this->components->info(sprintf('Audit chain intact (%d events checked).', $result->eventsChecked));

            return self::SUCCESS;
        }

        $logger->critical('audit.chain_broken', ['first_broken_event_id' => $result->firstBrokenEventId]);
        $this->components->error(sprintf('Audit chain broken at event #%d.', $result->firstBrokenEventId));

        return self::FAILURE;
    }
}
