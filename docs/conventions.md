# Conventions

Conventions are enforced by static analysis; CI-quality checks run in seconds
via `make lint`. Anything documented here that drifts will fail the build.

## PHP code style

- **`declare(strict_types=1);`** is mandatory on every PHP file.
- Handlers, controllers, value objects, and bus adapters are declared
  **`final readonly class`**. The exception is Symfony console commands, which
  extend `Symfony\Component\Console\Command\Command` and therefore cannot be
  `readonly` — for those, `final class` with `readonly` constructor properties.
- **Constructor property promotion** for all dependencies.
- **Marker interfaces** discriminate intent (`CommandInterface`,
  `QueryInterface`, `CommandHandlerInterface`, `QueryHandlerInterface`). They
  carry no methods; they exist for service tagging via `_instanceof` and for
  type clarity.
- HTTP entry points are named `*Port` (e.g. `ExamplePort`) and use
  `__invoke()` with the `#[AsController]` + `#[Route]` attributes.
- Symfony console commands declare their `name` and `help` text via
  `#[AsCommand]` rather than `setName()` / `setHelp()`.

## Naming and namespacing

| Element                  | Pattern                                                    |
| ------------------------ | ---------------------------------------------------------- |
| Module                   | `App\<Module>\…` (PascalCase, e.g. `App\Base\…`)            |
| Application command DTO  | `App\<Module>\Application\<Feature>\Command\<Name>Command` |
| Application command handler | …`\<Name>CommandHandler`                                |
| Application query DTO    | `App\<Module>\Application\<Feature>\Query\<Name>Query`     |
| Application query handler | …`\<Name>QueryHandler`                                    |
| HTTP port                | `App\<Module>\Infra\Ui\<Name>Port`                         |
| CLI port                 | `App\<Module>\Infra\Cli\<Name>Command`                     |

Test classes mirror the source path: `tests/Unit/<Module>/<Name>Test.php` for
unit tests, `tests/Functional/<Module>/<Name>Test.php` for HTTP-level
`WebTestCase` tests.

## Static analysis stack

Run all three with `make lint`. Each can also be run individually.

### PHPStan — `make phpstan`

Configured at the highest practical strictness:

- `phpstan/phpstan` core (level: max per project policy)
- `phpstan/phpstan-strict-rules` (no implicit booleans, strict comparisons)
- `phpstan/phpstan-deprecation-rules` (fails on deprecated symbol usage)

Cache lives under `var/cache/phpstan`. Clear it with `make phpstan-cc` after
upstream rule changes.

### Rector — `make rector` (dry-run) / `make rector-fix` (apply)

Automated refactoring. Active prepared sets (see `rector.php`):

`deadCode`, `codeQuality`, `codingStyle`, `typeDeclarations`, `privatization`,
`instanceOf`, `earlyReturn`, `rectorPreset`, `phpunitCodeQuality`,
`doctrineCodeQuality`, `symfonyCodeQuality`, `symfonyConfigs`.

Plus `withPhpSets()`, `withAttributesSets()`, and
`withComposerBased(doctrine: true, phpunit: true, symfony: true)` so version
upgrades automatically pull in the matching modernization rules.

### ECS (Easy Coding Standard) — `make ecs` / `make ecs-fix`

Style rules sourced from `mikelgoig/easy-coding-standard-rules`. List active
checkers with `make ecs-list`.

## Test strategy

One framework, two suites — both run by `make test`. The split lets you target
each suite independently from CI or local commands without changing tools.

### Unit — `make unit-test`

- Plain `PHPUnit\Framework\TestCase` subclasses under `tests/Unit/`.
- No kernel boot, no HTTP — pure object-level assertions.
- Use `#[CoversClass]` + `#[DataProvider]` + `#[Test]` PHPUnit attributes (the
  `requireCoverageMetadata="true"` flag in `phpunit.xml.dist` enforces coverage
  metadata on every test).

### Functional — `make func-test`

- Symfony `WebTestCase` subclasses under `tests/Functional/`. They boot the
  real kernel through `static::createClient()` and exercise the HTTP layer
  end-to-end via Symfony's BrowserKit client.
- Use the assertion helpers from `WebTestAssertionsTrait`:
  `assertResponseIsSuccessful()`, `assertResponseStatusCodeSame()`,
  `assertResponseHeaderSame()`, `assertJsonStringEqualsJsonString()`, etc.
- For functional tests of HTTP endpoints (no single class under test), mark the
  class with `#[CoversNothing]` so coverage metadata is still satisfied.

Suite selection is by name (lowercase, matching `phpunit.xml.dist`):

```console
make test                                   # both suites
make unit-test                              # only tests/Unit
make func-test                              # only tests/Functional
make func-test TEST_FILTER='--filter ExampleTest'
```

## Composer

- `composer.json` pins Symfony to `8.0.*` and uses the `extra.symfony.require`
  field so Flex restricts the Symfony skeleton to that major.
- `config.bump-after-update: true` auto-tightens constraints after each update
  so the lock file and the manifest stay in sync.
- `config.sort-packages: true` keeps `require` / `require-dev` alphabetical.
- The Symfony polyfills for `ctype`, `iconv`, and `php72`–`php82` are
  **replaced** (declared as `*` under `replace`) because PHP 8.5 ships them
  natively — see `composer.json:replace`.
