# Infrastructure

The project is delivered as a single Docker image that ships PHP, the
application server, the web server, and the real-time hub in one process. The
base template is [`dunglas/symfony-docker`](https://github.com/dunglas/symfony-docker);
this document describes the project-specific choices on top of it.

## Runtime stack

| Component       | Version / Notes                                                                |
| --------------- | ------------------------------------------------------------------------------ |
| PHP             | **8.5** (ZTS build, from `dunglas/frankenphp:1-php8.5`)                        |
| FrankenPHP      | **1.x** — Caddy + PHP fused as a single binary, runs in worker mode            |
| Caddy           | Bundled inside FrankenPHP; auto-HTTPS via Let's Encrypt in prod, local CA in dev |
| Mercure         | Caddy module, transport `bolt:///data/mercure.db` (configured in Caddyfile)    |
| Vulcain         | Caddy module for HTTP/2 Server Push-based hypermedia                           |
| Symfony         | **8.0**                                                                        |
| Messenger bus   | sync, single bus (`messenger.bus.default`); no transport configured yet        |
| PostgreSQL      | **18** (`postgres:18-alpine`), reached over DBAL; data in the `database_data` volume |
| Redis           | **7** (`redis:7-alpine`), backs the application cache (`cache.app`) and the lock store |
| Doctrine        | DBAL + Migrations (ORM installed but `auto_mapping` off — see `config/packages/doctrine.yaml`) |

PHP extensions installed in the image: `apcu`, `intl`, `opcache`, `redis`,
`zip`, plus `pdo_pgsql` (Doctrine), and `xdebug` in the dev/test targets only.

## Container layout

`Dockerfile` is a multi-stage build with these targets:

- **`frankenphp_upstream`** — pins the base image (`dunglas/frankenphp:1-php8.5`).
- **`frankenphp_base`** — adds Composer, PHP extensions, and copies the
  Caddyfile + entrypoint. This is the shared parent for dev and prod.
- **`frankenphp_dev`** — adds Xdebug + a non-root `nonroot` user with passwordless
  sudo. Configured by `compose.override.yaml` with
  `FRANKENPHP_WORKER_CONFIG: watch` (auto-reload on source change) and
  `FRANKENPHP_SITE_CONFIG: hot_reload` (per-site reload). Source is bind-mounted
  at `/app`.
- **`php_test`** — separate non-ZTS image (based on `php:8.5-cli`) used by the
  `php-test` compose service. PHPUnit and Infection run here; the FrankenPHP
  container can't because both PHP coverage drivers (Xdebug and pcov) are
  unreliable on the ZTS build FrankenPHP requires. Bind-mounts the same `/app`
  as the runtime, so `vendor/` is shared.
- **`frankenphp_prod_builder`** — runs `composer install --no-dev` and dumps the
  optimized autoloader and prod env.
- **`frankenphp_prod`** — minimal `debian:13-slim` runtime; only the FrankenPHP
  + PHP binaries, required shared libraries, the compiled app, and the CA
  bundle are copied in. Runs as `www-data`.

The dev target is built by default via `compose.override.yaml`; production is
built explicitly:

```console
docker compose -f compose.yaml -f compose.prod.yaml build --pull --no-cache
```

## Caddy / worker configuration

Two files matter:

- **`frankenphp/Caddyfile`** — the full Caddy config. The worker block lives
  *inside* the `php` directive (the modern FrankenPHP pattern), so the worker
  runs `public/index.php` as a long-lived process and Symfony Runtime keeps the
  kernel hot between requests.
- **`frankenphp/conf.d/`** — PHP INI fragments. `10-app.ini` is the base,
  `20-app.dev.ini` overlays Xdebug-friendly settings in dev.

Mercure is wired directly inside the Caddyfile's `mercure { ... }` block with a
`transport bolt { path /data/mercure.db }` directive (the modern syntax — the
old `MERCURE_TRANSPORT_URL` env var is gone).

## Compose files

| File                    | Purpose                                                                    |
| ----------------------- | -------------------------------------------------------------------------- |
| `compose.yaml`          | Base service definition: `php`, `database` (Postgres), `redis`; ports (80/443 TCP + 443 UDP for HTTP/3), volumes for Caddy data/config + `database_data` + `redis_data`, Mercure env keys. `php` waits for `database` and `redis` to be healthy. |
| `compose.override.yaml` | Dev-only overrides: target `frankenphp_dev`, source bind-mount, Xdebug env, `FRANKENPHP_WORKER_CONFIG: watch`, `FRANKENPHP_SITE_CONFIG: hot_reload`, `MERCURE_EXTRA_DIRECTIVES: demo`; wires `php-test` to the `database` service (test DB gets a `_test` suffix) and exposes Postgres on an ephemeral host port |
| `compose.prod.yaml`     | Prod target build, locked JWT secrets, no source bind-mount                |

## Environment variables of note

Driven by Caddy / FrankenPHP; defined in compose files or `.env`:

| Variable                       | Default                                          | Meaning                                              |
| ------------------------------ | ------------------------------------------------ | ---------------------------------------------------- |
| `SERVER_NAME`                  | `localhost`                                      | Caddy site address                                   |
| `HTTP_PORT` / `HTTPS_PORT`     | `80` / `443`                                     | Host port mapping                                    |
| `FRANKENPHP_CONFIG`            | _(empty)_                                        | Extra global FrankenPHP directives                   |
| `FRANKENPHP_WORKER_CONFIG`     | _(empty)_ / `watch` in dev                       | Extra worker directives                              |
| `FRANKENPHP_SITE_CONFIG`       | _(empty)_ / `hot_reload` in dev                  | Extra per-site `php` directives                      |
| `MERCURE_PUBLISHER_JWT_KEY`    | `!ChangeThisMercureHubJWTSecretKey!`             | Mercure publisher key                                |
| `MERCURE_SUBSCRIBER_JWT_KEY`   | same default                                     | Mercure subscriber key                               |
| `MERCURE_URL` / `_PUBLIC_URL`  | derived from `SERVER_NAME`                       | App-side URLs to the hub                             |
| `MERCURE_EXTRA_DIRECTIVES`     | `demo` (dev only)                                | Extra Caddyfile directives inside `mercure { … }`    |
| `XDEBUG_MODE`                  | `off`                                            | Switch Xdebug on with `develop`, `debug`, `profile`, … |
| `APP_ENV`                      | `dev` (override), `prod` (in prod compose)       | Symfony environment                                  |
| `DATABASE_URL`                 | `postgresql://app:!ChangeMe!@database:5432/app?serverVersion=18` | Doctrine DBAL connection                |
| `REDIS_URL`                    | `redis://redis:6379`                             | Application cache + lock backend                     |
| `LOCK_DSN`                     | `${DATABASE_URL}`                                | Symfony Lock store (Postgres advisory lock by default) |
| `POSTGRES_DB` / `_USER` / `_PASSWORD` | `app` / `app` / `!ChangeMe!`              | Postgres bootstrap credentials                       |

## Daily workflow (Makefile shortcuts)

`make` with no target prints the help banner. All commands run inside the `php`
container.

| Command                   | Description                                                  |
| ------------------------- | ------------------------------------------------------------ |
| `make build`              | Rebuild images from scratch (`--no-cache`)                   |
| `make start`              | `docker compose up -d --wait` (brings up `php`, `database`, `redis`) |
| `make down`               | Stop and remove orphans                                      |
| `make bash`               | Shell inside the `php` container                             |
| `make cache-clear`        | `bin/console cache:clear`                                    |
| `make restart-worker`     | POST to FrankenPHP's admin endpoint to restart the PHP worker |
| `make composer-install`   | Install dependencies                                         |
| `make composer-update`    | Update dependencies                                          |
| `make composer-validate`  | Strict validation of `composer.json`                         |
| `make migrate`            | Apply Doctrine migrations (`doctrine:migrations:migrate`)    |
| `make migration`          | Generate a migration from mapping diff (`doctrine:migrations:diff`) |
| `make lint`               | PHPStan + Rector (dry-run) + ECS                             |
| `make lint-fix`           | Rector + ECS in fix mode                                     |
| `make test`               | Run both suites in `php-test`; first clears the test cache and provisions/migrates the `app_test` DB (`TEST_FILTER=` supported) |
| `make unit-test`          | Only the `unit` suite (`tests/Unit/`) in `php-test`          |
| `make func-test`          | Only the `functional` suite in `php-test` (accepts `TEST_FILTER=`) |
| `make infection`          | Mutation testing in `php-test`                               |

## Local URLs

Once `make start` reports healthy:

- App: <https://localhost/api/example> (HTTPS via Caddy's local CA — accept the
  warning the first time, or trust the root certificate exported from the
  container at `/data/caddy/pki/authorities/local/root.crt`).
- Caddy admin (metrics, worker restart): <http://localhost:2019>
- Mercure hub: <https://localhost/.well-known/mercure>
