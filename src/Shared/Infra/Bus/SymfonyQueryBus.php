<?php

declare(strict_types=1);

namespace App\Shared\Infra\Bus;

use App\Shared\Application\Bus\QueryBusInterface;
use App\Shared\Application\Bus\Resultable;
use App\Shared\Application\Query\QueryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final readonly class SymfonyQueryBus implements QueryBusInterface
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    public function ask(Envelope|QueryInterface $message): Resultable
    {
        return new readonly class($this->bus->dispatch($message)) implements Resultable {
            public function __construct(
                private Envelope $envelope,
            ) {
            }

            public function getResult(): mixed
            {
                return $this->envelope->last(HandledStamp::class)?->getResult();
            }

            public function getEnvelope(): Envelope
            {
                return $this->envelope;
            }
        };
    }
}
