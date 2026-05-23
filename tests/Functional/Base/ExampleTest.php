<?php

declare(strict_types=1);

namespace App\Tests\Functional\Base;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversNothing]
final class ExampleTest extends WebTestCase
{
    #[Test]
    public function get_api_example_returns_success_json(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/example');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $payload = json_decode(
            (string) $client->getResponse()->getContent(),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('success', $payload['status']);
        $this->assertSame('Query executed successfully', $payload['message']);
    }
}
