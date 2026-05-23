# Challenge

Symfony 8 / PHP 8.5 starter on FrankenPHP, organized around a small **CQRS**
core with a per-module hexagonal layout. The codebase is intentionally tiny —
one example bounded context (`Base`) with one command, one query, one HTTP
port, and one CLI port — to show the shape and conventions without imposing a
business domain.

Based on [`dunglas/symfony-docker`](https://github.com/dunglas/symfony-docker).

## Getting started

Prerequisites: **Docker** (with Compose v2) and GNU **`make`**.

```console
make start
```

Then open <https://localhost/api/example> (accept the Caddy local-CA warning
the first time). The response is:

```json
{"status":"success","message":"Query executed successfully"}
```

The same flow runs through the CLI:

```console
docker compose exec php ./bin/console app:example
```

## Daily commands

```console
make lint        # PHPStan + Rector + ECS
make test        # PHPUnit (unit + functional)
make bash        # shell inside the php container
```

Run `make` with no target for the full help banner.

## Documentation

- **[`CLAUDE.md`](CLAUDE.md)** — stack summary, quick commands, where the code lives.
- **[`docs/architecture.md`](docs/architecture.md)** — modules, layers, the CQRS bus, worked request flow.
- **[`docs/conventions.md`](docs/conventions.md)** — code style, static analysis stack, test strategy.
- **[`docs/infrastructure.md`](docs/infrastructure.md)** — Docker stack, Caddyfile, env vars, full Makefile reference.

## License

MIT — see [`LICENSE`](LICENSE).
