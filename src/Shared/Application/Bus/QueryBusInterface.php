<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

use App\Shared\Application\Query\QueryInterface;

interface QueryBusInterface
{
    public function ask(QueryInterface $message): Resultable;
}
