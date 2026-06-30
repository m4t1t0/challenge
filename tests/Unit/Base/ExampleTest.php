<?php

declare(strict_types=1);

namespace App\Tests\Unit\Base;

use App\Base\Application\Example\Command\ExampleCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExampleCommand::class)]
final class ExampleTest extends TestCase
{
    /**
     * @return iterable<string, array<string>>
     */
    public static function providerDescription(): iterable
    {
        yield 'one' => ['One'];
    }

    #[Test]
    #[DataProvider('providerDescription')]
    public function test_from_string(string $value): void
    {
        self::assertSame('One', $value);
    }
}
