<?php

declare(strict_types=1);

namespace App\Base\Infra\Ui;

use App\Base\Application\Example\Command\ExampleCommand;
use App\Base\Application\Example\Query\ExampleQuery;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/example', methods: Request::METHOD_GET,)]
#[AsController]
final readonly class ExamplePort
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {}

    public function __invoke(): Response
    {
        $this->commandBus->handle(new ExampleCommand());
        $message = $this->queryBus->ask(new ExampleQuery())->getResult();
        return new JsonResponse(
            [
                'status' => 'success',
                'message' => $message,
            ],
        );
    }
}
