<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity\Application\Listeners;

use Meridian\Modules\Identity\Domain\Events\SecurityEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Writes every authentication event to the application log as a structured
 * entry. Suspicious events are logged as warnings so they can be alerted on.
 */
final readonly class LogSecurityEvent
{
    public function __construct(private LoggerInterface $logger) {}

    public function handle(SecurityEvent $event): void
    {
        $this->logger->log(
            $event->isSuspicious() ? LogLevel::WARNING : LogLevel::INFO,
            $event->name(),
            $event->context(),
        );
    }
}
