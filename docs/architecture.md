# Architecture

The codebase follows a **modular layout** with a small **CQRS** core built on top of
Symfony Messenger. Each business capability lives in its own module under `src/`,
with a shared kernel (`Shared/`) holding framework-agnostic contracts and the
Symfony-specific adapters that satisfy them.

## Directory layout

```
src/
├── Kernel.php
├── Shared/                              # framework-agnostic kernel
│   ├── Application/
│   │   ├── Bus/
│   │   │   ├── CommandBusInterface.php  # handle(CommandInterface): void
│   │   │   ├── QueryBusInterface.php    # ask(QueryInterface): Resultable
│   │   │   └── Resultable.php           # getResult(): mixed
│   │   ├── Command/
│   │   │   ├── CommandInterface.php         # marker
│   │   │   └── CommandHandlerInterface.php  # marker
│   │   └── Query/
│   │       ├── QueryInterface.php           # marker
│   │       └── QueryHandlerInterface.php    # marker
│   └── Infra/
│       └── Bus/
│           ├── SymfonyCommandBus.php    # adapter over Messenger
│           └── SymfonyQueryBus.php      # adapter over Messenger
└── Base/                                # example bounded module
    ├── Application/
    │   └── Example/
    │       ├── Command/
    │       │   ├── ExampleCommand.php        # DTO implements CommandInterface
    │       │   └── ExampleCommandHandler.php # implements CommandHandlerInterface
    │       └── Query/
    │           ├── ExampleQuery.php          # DTO implements QueryInterface
    │           └── ExampleQueryHandler.php   # implements QueryHandlerInterface
    └── Infra/
        ├── Cli/
        │   └── ExampleCommand.php       # Symfony console command (port)
        └── Ui/
            └── ExamplePort.php          # HTTP controller (port)
```

The `Base` module is the placeholder for the first business context — new
contexts (e.g. `Booking`, `Catalog`, `Billing`) sit next to it at the same level,
each repeating the `Application/` + `Infra/` split internally.

## Layers and dependency direction

```
            ┌─────────────────────────────────────────┐
            │  Infra (Ui, Cli, Persistence adapters)  │
            └──────────────────┬──────────────────────┘
                               │ depends on
                               ▼
            ┌─────────────────────────────────────────┐
            │  Application (Commands, Queries, Buses) │
            └──────────────────┬──────────────────────┘
                               │ depends on
                               ▼
            ┌─────────────────────────────────────────┐
            │  Domain (entities, value objects)       │   ← not yet present
            └─────────────────────────────────────────┘
```

- **Application** code depends only on its own DTOs/interfaces and the Shared
  kernel contracts. It must not reference any concrete Symfony class.
- **Infra** code is the only layer allowed to import Symfony components
  (controllers, console, Messenger, HTTP foundation, …) and to implement Shared
  interfaces.
- Ports (HTTP controllers, CLI commands) depend on `CommandBusInterface` /
  `QueryBusInterface`, never on concrete handlers.

There is no `Domain/` directory yet because no business rules have been written;
when one appears it will live in `src/<Module>/Domain/` and depend on nothing.

## CQRS bus

The two buses are thin wrappers around a single `Symfony\Component\Messenger\MessageBusInterface`:

- **`SymfonyCommandBus::handle(CommandInterface)`** — dispatches and discards
  the result. Commands return `void` by contract.
- **`SymfonyQueryBus::ask(QueryInterface): Resultable`** — dispatches and wraps
  the resulting `Envelope` in an anonymous `Resultable` so callers retrieve the
  value via `->getResult()` without ever touching `HandledStamp` directly.

Handlers are auto-registered via Symfony's `_instanceof` mechanism in
`config/services.yaml`:

```yaml
_instanceof:
    App\Shared\Application\Command\CommandHandlerInterface:
        tags:
            - { name: 'messenger.message_handler', bus: 'messenger.bus.default', method: 'handle' }
    App\Shared\Application\Query\QueryHandlerInterface:
        tags:
            - { name: 'messenger.message_handler', bus: 'messenger.bus.default', method: 'handle' }
```

That means **adding a handler does not require touching configuration** — just
implement the marker interface and define a `handle()` method whose first
parameter type-hints the message it serves.

Both buses currently share `messenger.bus.default`. Routing in
`config/packages/messenger.yaml` is empty, so all messages run **synchronously**
in-process. Switching to async (e.g. for commands only) is a configuration
change in that file plus a transport DSN.

## Request flow (worked example)

A `GET /api/example` call goes through:

1. **`Base/Infra/Ui/ExamplePort`** (HTTP controller, `#[AsController]` +
   `#[Route]`).
2. The port dispatches `new ExampleCommand()` through `CommandBusInterface`.
3. Messenger routes it to `ExampleCommandHandler::handle()` which writes a log
   line.
4. The port then dispatches `new ExampleQuery()` through `QueryBusInterface`.
5. Messenger routes it to `ExampleQueryHandler::handle()` which returns
   `'Query executed successfully'`.
6. The port unwraps the result via `Resultable::getResult()` and returns a
   `JsonResponse` with `{ status: 'success', message: ... }`.

The CLI command `app:example` follows the exact same flow through
`Base/Infra/Cli/ExampleCommand` — two ports, one application core.

## Adding a new feature

For a feature `X` inside a new module `Booking`:

1. Define the DTO and handler under
   `src/Booking/Application/X/Command/` (or `Query/`).
2. Make the DTO implement `CommandInterface` (or `QueryInterface`) and the
   handler implement `CommandHandlerInterface` (or `QueryHandlerInterface`)
   with a `handle()` method.
3. Add the port that triggers it under `src/Booking/Infra/Ui/` (HTTP) or
   `src/Booking/Infra/Cli/` (console). Inject the bus interface, never the
   concrete handler.
4. Write tests under `tests/Unit/Booking/...` and `tests/Functional/Booking/...`
   mirroring the source tree.

No configuration file edit is required; service autowiring + `_instanceof`
tagging handles registration.
