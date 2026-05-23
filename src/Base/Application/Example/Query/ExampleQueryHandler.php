<?php

declare(strict_types=1);

namespace App\Base\Application\Example\Query;

use App\Shared\Application\Query\QueryHandlerInterface;

final readonly class ExampleQueryHandler implements QueryHandlerInterface
{
    public function handle(ExampleQuery $query): string
    {
        return 'Query executed successfully';
    }
}
