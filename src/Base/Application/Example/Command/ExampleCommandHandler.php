<?php

declare(strict_types=1);

namespace App\Base\Application\Example\Command;

use App\Shared\Application\Command\CommandHandlerInterface;
use Psr\Log\LoggerInterface;

final readonly class ExampleCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function handle(ExampleCommand $command): void
    {
        $this->logger->info('ExampleCommand handled');
    }
}
