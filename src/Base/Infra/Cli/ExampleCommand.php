<?php

declare(strict_types=1);

namespace App\Base\Infra\Cli;

use App\Base\Application\Example\Command\ExampleCommand as ApplicationExampleCommand;
use App\Base\Application\Example\Query\ExampleQuery;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;
use Assert\Assertion;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:example', help: <<<'TXT'
This command is a base command for future implementations.
TXT)]
final class ExampleCommand extends Command
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Example command.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->commandBus->handle(new ApplicationExampleCommand());

        $message = $this->queryBus->ask(new ExampleQuery())->getResult();
        Assertion::string($message);

        $output->writeln('This is an example command.');
        $output->writeln($message);

        return Command::SUCCESS;
    }
}
