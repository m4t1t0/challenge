# CLAUDE.md

A Symfony 8 / PHP 8.5 starter built on FrankenPHP, structured as a small **CQRS**
core with a per-module hexagonal layout. Designed to be a clean base for adding
business features one bounded context at a time.

## Stack at a glance

- **PHP 8.5** running under **FrankenPHP 1.x** (Caddy-based, worker mode)
- **Symfony 8.0** — `framework-bundle`, `console`, `runtime`, `messenger`,
  `dotenv`, `yaml`
- **Symfony Messenger** as the CQRS bus (sync, single bus)
- **PostgreSQL 18** via **Doctrine DBAL + Migrations** (ORM installed, `auto_mapping` off)
- **Redis 7** backing the application cache and the **Symfony Lock** store
- **Mercure** as a Caddy module for real-time, **Vulcain** for HTTP/2 push
- **PHPUnit 12** for both unit tests and functional HTTP tests (Symfony `WebTestCase`)
- **Infection** for mutation testing (runs in a dedicated non-ZTS test container)
- **PHPStan** (max level, strict-rules `allRules`), **Rector**, **ECS** for static analysis and style

## Read next

| Topic                                       | File                                          |
| ------------------------------------------- | --------------------------------------------- |
| Module layout, CQRS bus, request flow       | [`docs/architecture.md`](docs/architecture.md) |
| Code style, naming, static analysis, tests  | [`docs/conventions.md`](docs/conventions.md)   |
| Docker stack, Caddyfile, Makefile, env vars | [`docs/infrastructure.md`](docs/infrastructure.md) |
| Ubiquitous language per bounded context     | [`CONTEXT.md`](CONTEXT.md) (template)          |
| Architecture decisions + ADR template       | [`docs/adr/`](docs/adr/README.md)              |

## Quick commands

All commands run inside the `php` container via the project Makefile.

```console
make start              # boot the dev stack (https://localhost)
make bash               # shell into the container
make composer-update    # update dependencies
make migrate            # apply Doctrine migrations
make lint               # PHPStan + Rector + ECS
make lint-fix           # apply Rector + ECS fixes
make test               # unit + functional tests (provisions the test DB)
make infection          # mutation testing
```

Full list: `make` (no target) prints the help banner.

## Where to start reading code

- **Bus contracts** — `src/Shared/Application/Bus/` (`CommandBusInterface`,
  `QueryBusInterface`, `Resultable`).
- **Bus adapters** — `src/Shared/Infra/Bus/` (`SymfonyCommandBus`,
  `SymfonyQueryBus`).
- **Worked example end-to-end** — `src/Base/Infra/Ui/ExamplePort.php` (HTTP) and
  `src/Base/Infra/Cli/ExampleCommand.php` (CLI) both call into
  `src/Base/Application/Example/` handlers through the buses.

## Conventions in one line

`declare(strict_types=1)` everywhere · `final readonly` for handlers, ports and
adapters · marker interfaces (`CommandHandlerInterface`, `QueryHandlerInterface`)
auto-tag handlers as Messenger handlers via `instanceof` in `config/services.php`,
so adding a feature is **zero-config**.
